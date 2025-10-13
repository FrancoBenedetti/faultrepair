# Rules to be applied 
 * Do not change the apache configuration  or .htaccess files without checking with me first. These have been set up to closely match the eventual xneelo deployment. 
 * Place all test php scripts in /backend/public unless there is a good, reportable reason to place these elsewhere.
 * After making changes to the front end, and before testing using the virtualhost http://snappy.local, run the snappy-build.sh script that copies the application files and symlinks to a separate folder outside the project for testing and distribution
 * An ngrok service is used to test behaviour on smartphones. The ngrok service is used to tunnel to snappy.local. Do not mess with this.
 * To keep the code uncluttered, test scripts for php must all be placed in the folder /backend/test/


 ## Implementation rules

 * Always use apiFetch for authenticated API calls
 * Reserve direct fetch for:
   * Public endpoints (no authentication needed)
   * Third-party API calls
   * File uploads (which may need different header handling)
