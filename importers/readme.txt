Project Importer v4.2
CaseySoftware, LLC
webmaster@caseysoftware.com

The Project Importer handles importing project plans from other tools into 
Web2project.  At present, the following tools are supported for import:
*  Microsoft Project 2003 and earlier;
*  Microsoft Project 2007 - when saved as a Project 2003 XML file;
*  WBS Gantt Chart Pro v4.4 - other versions may be supported, this specific
version has been validated;

COMPATIBLE VERSIONS

=====================================

*  All releases of this module as of v3.0 are known to be incompatible with 
PHP4.  Do not try to use it, do not ask us to backport the module.  PHP4 
reached it's End of Life on 08 August 2008.  Please upgrade to PHP5 as soon 
as your organization is ready.

*  All releases of this module starting with v3.3 have been validated to work
with the prior Web2project v1.3 through the upcoming v2.1.  It is not
compatible with any version of dotProject and no future releases will support 
dotProject.

KNOWN/PREVIOUS ISSUES

=====================================

Open Issues:

*  By default, the Importer will attempt to create users for any resources 
(aka people) it does not recognize.  If the resource name (username) is below 
the minimum allowed length in Web2project (default: 4), they will not be able 
to log into the system.  It is recommended that you adjust any usernames to 
meet this requirement.

*  The Microsoft Project import class still uses the php4 style of XML parsing.
It should be updated to use SimpleXML or something similar to speed processing
and reduce memory use.

Updated in 4.2:

*  Added numerous notes on areas to cleanup & simplify in future versions;

*  Applied proper theming to all form fields;

*  Fixed the Contact lookups to work more often;

*  Fixed the Task Assignees to allow existing users (not just newly imported 
users) to be assigned to Tasks;

*  Fixed the Task Assignees to respect permissions and limit the selection only
to Users who can actually view Tasks;

*  Fixed the "View the project here" link;

*  Fixed the Work Breakdown Structure id for the WBS Pro file format;

*  Refactored the flow of the module to the 'proper' structure now specified in
the web2project Module Building Guide;

*  Refactored the post-upload step to a clean redirect with the preview;

*  Removed the references to the raw $_FILES;

*  Updated the class structures to standardize look & feel;

*  Updated the deprecated DBQuery to the new w2p_Database_Query class;

*  Updated the project/task processing to be timezone aware;

*  Updated the status messages to use the AppUI status messages instead of echo;

Updated in 4.1:

*  Fixed a major issue where dependencies were not properly imported or
sometimes at all.

*  Added a tweak to the MS Project processor to make sure the project title is
retrieved properly.

*  Fixed an issue where the Project Start Date was not set properly.

*  Tweaked the import process so that if there's no Company match, it default's
to the current User's Company. This resolves the issue where a Project could
accidentally get assigned to no Company and therefore disappear due to
permissions.

*  Changed the licensing to be in accordance with the the coming web2project
shift.

Updated in 4.0:

*  Added some hr tags and lined up some input boxes to make the UI cleaner.

*  Updated the module structure (paths and names) to use the core web2project
autoloader and simplify the includes code.

*  Refactored much of the Project/Task/Contact creation code into its proper
class methods instead of raw DBQuery calls. Updated those calls to use the 
object validation results. Further moved this code into the base CImporter
class to reduce duplication.

*  Applied some major patches from Alain Picard to handle larger and larger
file sizes. In most cases, this is a 20-25% improvement, but some imports can
get as much as an 80% improvement in processing time.
    -    ~95 Tasks process in   ~4 seconds using  12MB at peak;
    -   ~450 Tasks process in  ~50 seconds using  15MB at peak;
    -  ~1100 Tasks process in ~180 seconds using 380MB at peak;


Updated in 3.2:

*  Fixed a bug in import where it simply wasn't importing Projects unless 
you had some odd combination of permissions... now it's be simplified to 
just requiring the Project:Add permission;

*  Converted a bunch of the raw DBQuery calls to calls on the proper objects,
this doesn't do anything with the results of the object validation, but 
that's not formally in Web2project until v1.2 anyway;

Updated in 3.1:

*  Simplified the permissions checking to only check for Project Add 
privileges;

*  Removed the poorly implemented translation files to take advantage of the 
reworked translation handler in Web2project v1.1;

*  Simplified the View to use a case statement instead of nested if's.  The 
code isn't shorter, but it's quite a bit clearer;

*  General clean up to use core Web2project functionality as opposed to using 
custom code, it isn't complete;

Fixed in 3.0:

*  Updated the Project Importer to support Web2project.  In the process, 
backwards compatibility with dotProject was lost.  This is a known condition 
and is not considered an issue or bug, it is a design decision and will not 
be reversed or reconsidered.

*  Simplified the class structure and encapsulated more of the type-specific 
details away from the user and the BaseImporter class.

*  Converted some of the raw SQL statements to use the DBQuery class.

Fixed in 2.2:

*  Added a contribution from Wellison da Rocha Pereira to allow for importing 
of WBS Gant Chart Pro v4.4 files.

*  Added a contribution from Richard Siomporas to allow the User to choose 
whether or not to import Users from the imported file.

*  Found and resolved an issue where it was possible to assign a project to 
Company 0 which would prevent all users from accessing the project in any way.

Fixed in 2.1:

*  Fixed an issue with the task_type which was only being set consistently for 
dynamic tasks.  This would cause some tasks not to display in the Project View.

*  If you attempt to import a project and there is already one with an 
identical name, the Importer will automatically delete the existing tasks and 
import the new tasks into the existing project.  A warning will appear to the 
user during the preview stage.

Fixed in 2.0:

*  Fixed the parent/child relationship error.  In the v1.5 release, a bug was 
introduced which could cause parent/child relationships to be lost.

*  Fixed the User case mismatch error.  Previously, when the Importer created 
Users, it would use the same capitalization from the Microsoft Project file's 
Resources.  Unfortunately, this often created sitatuations wherea a new User 
named "Keith" was created while "keith" was an existing User.  The Importer 
now makes all usernames lowercase to match the rest of the system.

*  Improved overall memory management.  Previously, with 64M allocated to PHP, 
approximately 100 tasks in a single project plan could be imported.  With the 
new memory management, 64M has proven sufficient to import 450+ tasks.

*  Created a simple class hierarchy.  Previously, only a single preview() and 
import() could be supported.  By creating a simple class structure, the goal 
is to more easily support additional filetypes in future releases.

*  Adjusted the install() and remove() functions in the setup procedure to 
automatically attempt to install the translation files (English and German 
currently) to their proper locations within dotProject.  This ensures that all 
language changes via the Translation Management are reflected.

Fixed in 1.5:

*  Fixed the duration rounding error.  Previously tasks with fractional hour 
durations were being rounded down to the nearest hour.  Now the actual value 
is perserved.

*  Fixed the character cutoff issue.  Due to how PHP4 handles XML parsing and 
cdata blocks, special characters were triggering the parser to treat the 
single cdata node as multiple node therefore causing only the last of the 
text segments to be imported.

*  Various raw SQL updates were replaced with their DBQuery equivalents.

Fixed in 1.4:

*  Tweaked the resource import.  Previously there was no way to completely 
remove a person.  Changing a user's assignment to zero removes them from the 
task completely.

*  Fixed the resource import.  Previously, if a task had no one assigned the 
import process would generate an error.  It now checks the array size before 
attempting to iterate over it.

*  Added the ability to chose a Project Status during import.

*  Added the ability to chose a Project Owner during import, defaults to the 
user doing the import.

*  Modified the preview screen to warn the user if a username is below the 
minimum length set in their dotProject configuration.

*  Modified the preview screen to warn the user about permissions if the 
module is about to create a new Company.

*  Modified the import functionality to use the properly scrubbed parameters, 
etc via the core dPgetParam functions.

*  Cleaned up the code to better match the coding standards in use by 
dotProject (PEAR).

*  Added the security sentinal from core dotProject to prevent unauthorized 
access via the filesystem.  This breaks compatibility with all dotProject 
releases prior to 2.1-rc1.

*  Renamed the module from the "Microsoft Project Importer" to simply "Project 
Importer".  This is to avoid any potential legal issues and acknowledging the 
planned direction of the module.

Fixed in 1.3:

*  All user assignments are now calculated from the values in the Microsoft 
Project file itself.  This insight was shared by the highly esteemed Tim 
Millhouse of Digilore, Inc and implemented by D. Keith Casey, Jr. of 
CaseySoftware, LLC.

Planned:

*  Currently, the module uses the DBQuery object to create the required Company, 
Project, and Tasks.  This should be coverted to use the standard dotProject 
objects to simplify and ensure future compatibility.

*  The module should be scrubbed of the inline html/php nastiness.

*  Currently, the module it uses the PHP4 style of event-based parsing for 
the XML document and should be converted to use PHP5's SimpleXML parser.  It 
will cause the module to be smaller, easier to maintain, and probably more 
flexible over all.  This will not happen any time soon as it breaks PHP4 
compatibility.

*  Support importing directly from Gantt Project, Planner, and Task Juggler.
There is no timeline for these formats but that is the likely order.

INSTALL

=====================================

0.  Previous installations of this module can simply be removed via the System
Admin -> View Modules screen.

1.  To install this module, please follow the standard module installation 
procedure.

2.  Download the latest version from CaseySoftware.com and unzip the
file. Take the existing directory (called: importers) and move/upload it into
your web2project/modules directory.

3.  Select to System Admin -> View Modules and you should see "Project
Importer" near the bottom of the list.

4.  On the "importer" row, select "install".  The screen should
refresh and the line should become "Project Importer".  Now select "hidden"
and then "disabled" to make it display in your module navigation.

5.  You should be able to order it within the navigation like any other module.

USAGE

=====================================

1.  Within Microsoft Project, open your project as usual.  In the File Menu, 
select "Save AS ...".  Under file-type, choose "xml" and save your file.

2.  Within Web2project, select "Project Import" from your module navigation.  

3.  Select "Browse", select the file you saved in Step 1, and select "Import Data".

4.  Your screen should now show a summary of the imported project with all the 
relevant Company, Project, Task, and Assignment information.

5.  If a matching Company was found, the Importer will automatically assign 
it.  Alternatively, if there was no matching Company, you can choose one or 
allow the Importer to create a new one.

6.  Input a Project Name.  If the imported name is already in use, the module 
will note it here.

7.  Assign the usernames who match your resources.  Any users not mapped to 
current Web2project users will automatically be created in an Inactive state.

8.  Adjust the task assigments as necessary.  By default, the percent 
allocations from the MS Project file will be used.

9.  Select "import" below.


If you find this module particularly useful and would like to express 
gratitude or seek additional development, please do not hesitate to contact 
CaseySoftware, LLC via webmaster@caseysoftware.com

Microsoft Project is an application owned by Microsoft Corporation.  All Rights Reserved.