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
      nodejs-18_x
      nodePackages.yarn
      php81WithExtensions
    ];
  }
