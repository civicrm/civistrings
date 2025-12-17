{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2025-12-17 04:09 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/e9aa5e95195ac82d6058c6929887ea91917f14e3.tar.gz";
  sha256 = "1difffv64aam9jkcn62iqg2v6wccr23r29c624s7l045maby5ak9";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
