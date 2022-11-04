{pkgs ? import <nixpkgs> {}}:
with pkgs;
  mkShell rec {
    buildInputs = [
      alejandra
      nodejs-18_x
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
    ];
  }
