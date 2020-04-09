# test_redirect_everything
Ephemeral repository to hold a test suit for a PR

Script to test https://github.com/Automattic/jetpack/pull/15140

## Results

You don't need to run this script, you can simply see the [results here](report).

## Running

If you want to run, here's what's needed:

* Jetpack repository
* wpcom repository with D40940-code
* Clone this repo
* `composer install`
* Check the paths at the beginning of `test.php` that point to the wpcom repo
* Check the branches names you are comparing in the beginning of `test.php`
* run in the terminal `php test.php`
