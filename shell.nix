{
  pkgs ? import <nixpkgs> {},
  php81WithExtensions,
}:
with pkgs;
  mkShell rec {
    buildInputs = [
      alejandra
      nodejs-18_x
      nodePackages.yarn
      php81WithExtensions
      (php81Packages.composer.override {php = php81WithExtensions;})
    ];
  }
