{
  description = "Pterodactyl Panel";

  inputs = {
    dream2nix = {
      url = "github:nix-community/dream2nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };

    flake-utils = {
      url = "github:numtide/flake-utils";
    };

    mk-node-package = {
      url = "github:winston0410/mkNodePackage";
      inputs = {
        flake-utils.follows = "flake-utils";
        nixpkgs.follows = "nixpkgs";
      };
    };

    nixpkgs = {
      url = "github:NixOS/nixpkgs/nixos-unstable";
    };
  };

  outputs = {
    self,
    dream2nix,
    flake-utils,
    mk-node-package,
    nixpkgs,
    ...
  }:
    flake-utils.lib.eachDefaultSystem (
      system: let
        version = "latest";

        pkgs = import nixpkgs {inherit system;};
        mkNodePackage = mk-node-package.lib."${system}".mkNodePackage;

        php81WithExtensions = with pkgs; (php81.buildEnv {
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
        });
        composer = with pkgs; (php81Packages.composer.override {php = php81WithExtensions;});

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

        configs = pkgs.runCommand "configs" {} ''
          mkdir -p $out/etc/caddy
          ln -s ${caddyfile} $out/etc/caddy/Caddyfile
          ln -s ${phpfpmConf} $out/etc/php-fpm.conf
        '';

        src = with pkgs.lib;
          cleanSource (cleanSourceWith {
            filter = name: type: let
              baseName = baseNameOf (toString name);
            in
              !(builtins.elem baseName [
                ".direnv"
                ".github"
                "bootstrap/cache"
                "node_modules"
                "public/build"
                "public/hot"
                "storage"
                "vendor"
                ".editorconfig"
                ".env"
                ".env.testing"
                ".envrc"
                ".gitignore"
                ".php-cs-fixer.cache"
                ".phpunit.result.cache"
                "BUILDING.md"
                "CODE_OF_CONDUCT.md"
                "CONTRIBUTING.md"
                "docker-compose.development.yaml"
                "docker-compose.example.yaml"
                "docker-compose.yaml"
                "flake.lock"
                "flake.nix"
                "shell.nix"
              ]);
            src = ./.;
          });

        app =
          (dream2nix.lib.makeFlakeOutputs {
            config.projectRoot = src;
            source = src;
            settings = [
              {
                translator = "composer-lock";
                subsystemInfo.noDev = true;
              }
            ];
            systems = [system];
          })
          .packages
          ."${system}"
          ."pterodactyl/panel";

        ui = mkNodePackage {
          inherit src version;

          pname = "pterodactyl";
          buildInputs = [];

          buildPhase = ''
            yarn run build
          '';

          installPhase = ''
            mkdir -p $out
            cp -r public/build $out
          '';
        };

        panel = pkgs.stdenv.mkDerivation {
          inherit src version;

          pname = "pterodactyl";
          buildInputs = [app ui];

          installPhase = ''
            cp -r ${app}/lib/vendor/pterodactyl/panel $out

            chmod 755 $out
            chmod 755 $out/public

            mkdir -p $out/public/build
            cp -r ${ui}/build/* $out/public/build

            rm $out/composer.json.orig
          '';
        };
      in {
        defaultPackage = panel;
        devShell = import ./shell.nix {inherit composer php81WithExtensions pkgs;};

        packages = {
          inherit panel;

          development = with pkgs;
            dockerTools.buildImage {
              name = "pterodactyl/development";
              tag = "panel";

              copyToRoot = pkgs.buildEnv {
                name = "image-root";
                paths = [
                  dockerTools.fakeNss
                  caCertificates
                  caddy
                  composer
                  configs
                  coreutils
                  mysql80
                  nodejs-18_x
                  nodePackages.npm
                  nodePackages.pnpm
                  nodePackages.yarn
                  php81WithExtensions
                  postgresql_14
                ];
                pathsToLink = ["/bin" "/etc"];
              };
            };

          oci = with pkgs;
            dockerTools.buildImage {
              name = "pterodactyl/panel";
              tag = version;

              copyToRoot = buildEnv {
                name = "image-root";
                paths = [
                  dockerTools.fakeNss
                  caCertificates
                  caddy
                  configs
                  php81WithExtensions

                  panel
                ];
                pathsToLink = ["/bin" "/etc"];
              };

              config = {
                Cmd = [];
              };
            };
        };
      }
    );
}
