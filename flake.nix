{
  description = "Pterodactyl Panel";

  inputs = {
    flake-parts = {
      url = "github:hercules-ci/flake-parts";
      inputs.nixpkgs-lib.follows = "nixpkgs";
    };

    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    systems.url = "github:nix-systems/default";
  };

  outputs = {self, ...} @ inputs:
    inputs.flake-parts.lib.mkFlake {inherit inputs;} {
      systems = import inputs.systems;

      perSystem = {
        pkgs,
        system,
        ...
      }: let
        php = pkgs.php; # PHP 8.2

        phpWithExtensions = php.buildEnv {
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
        };

        composer = php.packages.composer.override {php = phpWithExtensions;};
      in {
        # Initialize pkgs with our overlays
        _module.args.pkgs = import inputs.nixpkgs {
          inherit system;
        };

        devShells.default = pkgs.mkShellNoCC {
          buildInputs = with pkgs; [
            composer
            nodejs_18
            nodePackages.pnpm
            phpWithExtensions
          ];

          shellHook = ''
            PATH="$PATH:${pkgs.docker-compose}/libexec/docker/cli-plugins"
          '';
        };

        packages.development = pkgs.dockerTools.buildImage {
          name = "pterodactyl/development";
          tag = "panel";

          copyToRoot = pkgs.buildEnv (let
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

                php_fastcgi localhost:9000 {
                  trusted_proxies private_ranges
                }

                try_files {path} {path}/ /index.php?{query}
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
          in {
            name = "image-root";
            paths = with pkgs; [
              (pkgs.runCommand "configs" {} ''
                mkdir -p "$out"/etc/caddy
                ln -s ${caddyfile} "$out"/etc/caddy/Caddyfile
                ln -s ${phpfpmConf} "$out"/etc/php-fpm.conf
              '')
              bash
              dockerTools.caCertificates
              dockerTools.fakeNss
              caddy
              composer
              coreutils
              mysql80
              nodejs_18
              nodePackages.pnpm
              nodePackages.yarn
              phpWithExtensions
            ];
            pathsToLink = ["/bin" "/etc"];
          });
        };
      };
    };
}
