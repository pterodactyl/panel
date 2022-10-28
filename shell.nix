{pkgs ? import <nixpkgs> {}}:
with pkgs;
  mkShell rec {
    buildInputs = [
      alejandra
      (php81.buildEnv {
        extensions = ({ enabled, all }: enabled ++ (with all; [
          redis
          xdebug
        ]));
        extraConfig = ''
          xdebug.mode=debug
        '';
      })
      php81Packages.composer
    ];
  }
