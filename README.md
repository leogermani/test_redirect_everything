# test_redirect_everything
Ephemeral repository to hold a test suit for a PR

Script to test https://github.com/Automattic/jetpack/pull/15140

## Results

You don't need to run this script, you can simply see the [results here](report).

## What this script does?

This script parses a `git diff` between the feature branch and master and look at URLs that were replaced by function calls.

When he finds a diff that has the same number of URLs and calls to the redirect URL builder, it compares them to see if the function is using the correct slug and pointing the link to the right place.

It does this by verifying the target array of redirects registered on the `jetpack-redirects` lib that lives in the wpcom repository.

## Running

If you want to run, here's what's needed:

* Jetpack repository
* wpcom repository with D40940-code
* Clone this repo
* `composer install`
* Check the paths at the beginning of `test.php` that point to the wpcom repo
* Check the branches names you are comparing in the beginning of `test.php`
* run in the terminal `php test.php`
