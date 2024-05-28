# Setup coding-standard-source

We need to make sure the coding-standard-source is installed and available
in your environment.

1) Clone the coding-standard-source repository
    ```bash
    git clone git@github.com:ZooRoyal/coding-standard-source.git
    ```
2) Install the dependencies
    ```bash
    composer install
    ```
   Notice how npm dependencies are also installed. This is because of a
   custom script in the composer.json file. ("post-install-cmd")

#### Optional

Check if everything works by running the coding-standard from source. For
this your need to switch to a git repository on your disc containing a composer.
json file and execute a test run.

```bash
cd /path/to/your/project
/path/to/coding-standard-source/tests/run-coding-standard.sh sca:all
```
The script understands the same parameters as the coding-standard
application. As it will build the coding-standard application from source it
will take a little longer then a normal run.
