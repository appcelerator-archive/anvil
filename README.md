# Anvil test framework

## Description
Anvil is a testing framework that replaces the old Drillbit testing framework.  The anvil test framework no longer depends on the old titanium desktop product and is now written entirely in  Javascript using titanium and node.js.

Anvil is made up of the following subcomponents:
1. Hub - web service that provides reporting, CI integration and remote control of overall test process.  It will be possible for the hub to kick off a test run by notifying the drivers to start off a test.
2. Driver - a node.js process that builds, deploys and manages a harness that runs on device or simulator
3. Harness - titanium app that runs on device or simulator and contains all the actual test suites that make up a anvil test pass

### Hub
The hub is a collection of various software components that live in a hosted linux environment and whose purpose is to serve as a bridge between driver instances and the CI server.  An additional role of the hub is to populate a mysql database that is used as the driving data source behind Anvil reports accessible via the web.  The hub serves as a TCP server that both the CI server and driver instances connect to.

### Driver
The role of the driver is to run the harness on device or simulator and get results back.  To that end, the driver is capable of creating a test harness from a template, building it, installing to device or simulator, running specified suites or tests and getting results back.

When running the driver, a logging level can be set that will control the level of detail printed to stdout.  The --log-level property can take "quiet", "normal" (default) or "verbose".  Log level "quiet" tells the driver to only print test results summary and error output.  Log level "normal" tells the driver to print "quiet" content along with normal operation output (creating directory, etc).  Log level "verbose" tells the driver to print "quiet" and "normal" content along with all debug info such as output seen when calling the platform specific build scripts.

For each test run, the driver creates log files that live in a logs directory under a configurable temp location that is usually:

```
"/tmp/driver/logs/<platform>/log_<month>-<day>-<year>_<hour>-<minute>-<second>-<millisecond>"
```

Logs are rotated (oldest deleted) as needed per platform based on a configuration property set in driver.js.  Logs contain all driver output regardless of the --log-level property specified when running the driver.

A normal driver run follows this general flow:
1. Load list of config sets and set the first set as the active set
2. Build harness based on first config in the active config set
3. Install harness to device / simulator
4. Establish connection between driver and harness (socket for Android/iOS and http request/response chain for mobile web).
5. Driver asks harness for list of suites via connection established in step 3
6. Driver asks harness for list of tests in current suite
7. Driver tells the harness to run the current test in the current suite
8. Harness returns results for specified test
9. repeat 7-8 till suite is finished and then go back to 5 until all suites have run
10. start over at 2 if there are more configurations to be run within the set
11. once all configurations are done, move to the next config set
12. if all config sets have run, print result summary

The Anvil Driver supports two modes of operation:

### Local Mode
When started, the driver runs in a interactive mode that allows the user to run commands via a command line interface.  It is also possible to run the driver in "one shot" local mode that does not start a command line interface but instead takes a command to run as a command line argument passed in when the driver is started.  This second mode is useful when trying to run a specific driver task from another script where command line interaction is not possible. 

Commands vary based on the platform that the driver is being run for but in general the commands supported are: create, build and start.  start is the main command that will be used for running tests and it will automatically uninstall an existing harness, create a new harness instance, deploy it to device or simulator, run the harness and report the results back.  When running the "start" command, the following arguments can be specified:
- "--config-set=<config set id>": specifies the configuration set to run (the name is defined in a "name.txt" file that needs to exist in the top level directory of the config set
- "--config=<config id>": specifies the configuration (specific app.js and tiapp.xml) to run
- "--suite=<suite name>": specifies the test suite to be run
- "--test=<test name>": specifies the individual test to be run ("--suite" must be specified for this to take effect)

It should be noted that an app.js file may exist in the "Resources" directory. This app.js file will be used as a default app.js for a config if the config does not contain a custom app.js file.  This default app.js is useful when a config set is more about testing a variety of tiapp.xml configurations against a common set of suites.

### Setup Notes
- In order to run the driver, node.js must be installed on the system where it is being run. Last validated node.js version is v0.8.8 and should be considered the minimum required version
- testing for mobile web and Android require adb to be present.  If you are unable to plug in a android device via usb and type "adb logcat" there is likely an environment issue
- driver testing for mobile web requires that your driver instance listen for http connections on an address that can be hit via a android test device on the same wifi network.  These driver configuration properties (not to be confused with harness configurations) currently can be set in the config.js file (creation instructions provided on launch if it does not exist) and the app.js file for the harness
- driver testing for ios uses the simulator only currently so no special dependency exists
- often times when running android/mw tests, switching to ios testing and then back to android/mw problems can be encountered in terms of adb and device connection.  when switching back from ios to android/mw it is suggested that adb be started (type "adb devices") and the device be unplugged and replugged in so that the connection is "reset"

## Usage

### Driver

```bash	
"node driver.js --mode=<mode> --platform=<platform> [--log-level=<level>][--command=<command>]"
```

- mode argument can be: "local" or "remote"
- platform argument can be: "android", "ios" or "mw" (mobile web)

### Example

```bash
# start driver in local mode for the android platform
"node driver.js --mode=local --platform=android"

# start driver in local mode for the mobile web platform with result summary only logging
"node driver.js --mode=local --platform=mw --log-level=quiet"

# start driver with one shot command for ios platform that will only run the suite "x" for configuration "1"
'node driver.js --mode=local --platform=ios --command="start --config=1 --suite=x"'

# start driver in remote mode for the iOS platform
"node driver.js --mode=remote --platform=ios"
```

Harness examples (via command line interface once driver is started or startup arguments when 
starting the driver):

```bash
# start test run for ONLY configuration "1"
"start --config=1"

# start test run for ONLY configuration "2" in the standard config set
	"start --config-set=standard --config=2"

# start test run for all configs in the standard config set
"start --config-set=standard"

# start test run for ALL configurations
"start"

# start test run for ALL configurations but only run the "x" test suite if it exists in each of the configurations
"start --suite=x"

# start test run for configuration "1", suite "y" and test "z"
"start --config=1 --suite=y --test=z"

# attempt to start a test run for all configurations and suites but only run the test "z" in each.  this DOES NOT WORK.  a --test argument is ignored unless a valid --suite argument is also specified
"start --test=z"
```
