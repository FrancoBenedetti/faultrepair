# Rules to be applied 
 * Do not change the apache configuration  or .htaccess files without checking with me first. These have been set up to closely match the eventual xneelo deployment. 
 * The public folder is used to test the system loacally in the development environment. Do not modify the public folder. 
 * Only update the public folder by running running rebuild-dev.sh
 * Place all test php scripts in /backend/public unless there is a good, reportable reason to place these elsewhere
 * After making changes to the front end, and before testing using the virtualhost http://fault-reporter.local,run rebuild-dev.sh