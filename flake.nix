{
  description = "Pterodactyl Panel";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = {
    self,
    nixpkgs,
    flake-utils,
    ...
  }:
    flake-utils.lib.eachDefaultSystem (
      system: let
        pkgs = import nixpkgs {inherit system;};

        caCertificates = pkgs.runCommand "ca-certificates" {} ''
          mkdir -p $out/etc/ssl/certs $out/etc/pki/tls/certs
          ln -s ${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt $out/etc/ssl/certs/ca-bundle.crt
          ln -s ${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt $out/etc/ssl/certs/ca-certificates.crt
          ln -s ${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt $out/etc/pki/tls/certs/ca-bundle.crt
        '';

        caddyfile = pkgs.writeText "Caddyfile" ''
          :80 {
          	root * /var/www/html/public/
          	file_server

          	header {
          		-Server
          		-X-Powered-By
          		Referrer-Policy "same-origin"
          		X-Frame-Options "deny"
          		X-XSS-Protection "1; mode=block"
          		X-Content-Type-Options "nosniff"
          	}

          	encode gzip zstd

          	php_fastcgi localhost:9000

          	@startsWithDot {
          		path \/\.
          		not path .well-known
          	}
          	rewrite @startsWithDot /index.php{uri}

          	@phpRewrite {
          		not file favicon.ico
          	}
          	try_files @phpRewrite {path} {path}/ /index.php?{query}
          }
        '';

        phpfpmConf = pkgs.writeText "php-fpm.conf" ''
          [global]
          error_log = /dev/stderr
          daemonize = no

          [www]
          user  = nobody
          group = nobody

          listen = 0.0.0.0:9000

          pm                      = dynamic
          pm.start_servers        = 4
          pm.min_spare_servers    = 4
          pm.max_spare_servers    = 16
          pm.max_children         = 64
          pm.max_requests         = 256

          clear_env = no
          catch_workers_output = yes

          decorate_workers_output = no
        '';

        configs = pkgs.runCommand "configs" {} ''
          mkdir -p $out/etc/caddy
          ln -s ${caddyfile} $out/etc/caddy/Caddyfile
          ln -s ${phpfpmConf} $out/etc/php-fpm.conf
        '';
      in {
        devShell = import ./shell.nix {inherit pkgs;};

        packages = {
          development = pkgs.dockerTools.buildImage {
            name = "pterodactyl/development";
            tag = "panel";

            copyToRoot = pkgs.buildEnv {
              name = "image-root";
              paths = with pkgs; [
                dockerTools.fakeNss
                caCertificates
                caddy
                configs
                coreutils
                mysql80
                nodejs-18_x
                nodePackages.npm
                nodePackages.pnpm
                nodePackages.yarn
                (php81.buildEnv {
                  extensions = {
                    enabled,
                    all,
                  }:
                    enabled
                    ++ (with all; [
                      redis
                      xdebug
                    ]);
                  extraConfig = ''
                    xdebug.mode=debug
                  '';
                })
                php81Packages.composer
                postgresql_14
              ];
              pathsToLink = ["/bin" "/etc"];
            };
          };
        };
      }
    );
}
