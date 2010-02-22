
Setup
================================================================================

* You will need to download and install the SimianGrid PHP grid services from
  <http://openmetaverse.googlecode.com/>.
* You will need the presence-refactor branch of OpenSim.
* Make sure this module (SimianGrid) has been checked out into the OpenSim 
  directory structure, under addon-modules/SimianGrid.
* Run OpenSim's runprebuild.bat or runprebuild.sh and recompile OpenSim.
* Copy the addon-modules/SimianGrid/ directory to bin/addon-modules/SimianGrid/
* Open your OpenSim.ini file. Make sure it is properly setup to run in grid mode
  before making any changes. Near the bottom of the config file, comment or 
  remove the entire [Architecture] section. You should still have a [Modules]
  section that contains the line:
    Include-modules = "addon-modules/*/config/*.ini" 

Running
================================================================================

* Make sure Apache, MySQL, SimianGrid, and optionally SimianGridFrontend are
  installed, configured, and properly running.
* Start OpenSim.exe and confirm that logins have been enabled for each running 
  region.

Using
================================================================================

You should now be able to register new user accounts with SimianGridFrontend and
login with any OpenSim-compatible client. Check OpenSim's logging and 
SimianGrid's services.log file for errors.
