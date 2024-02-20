{
  composer ? null,
  php81WithExtensions ? null,
  pkgs ? import <nixpkgs> {},
}:
with pkgs;
  mkShell rec {
    buildInputs = [
      alejandra
      composer
      nodejs_18
      nodePackages.yarn
      php81WithExtensions
    ];

    shellHook = ''
      PATH="$PATH:${pkgs.docker-compose}/libexec/docker/cli-plugins"
    '';
  }
