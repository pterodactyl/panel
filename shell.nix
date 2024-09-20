{
  composer ? null,
  phpWithExtensions ? null,
  pkgs ? import <nixpkgs> {},
}:
with pkgs;
  mkShell rec {
    buildInputs = [
      alejandra
      composer
      nodejs_18

      nodePackages.pnpm
      phpWithExtensions

      docker-compose
    ];

    shellHook = ''
      PATH="$PATH:${pkgs.docker-compose}/libexec/docker/cli-plugins"
    '';
  }
