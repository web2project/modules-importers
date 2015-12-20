# Project Importer

The Project Importer handles importing project plans from other tools into  Web2project.  At present, the following tools are supported for import:

* Microsoft Project 2003 and earlier;
* Microsoft Project 2007 - when saved as a Project 2003 XML file;
* WBS Gantt Chart Pro v4.4 - other versions may be supported, this specific version has been validated;

## Open Issues:

*  By default, the Importer will attempt to create users for any resources (aka people) it does not recognize.  If the resource name (username) is below the minimum allowed length in Web2project (default: 4), they will not be able to log into the system.  It is recommended that you adjust any usernames to meet this requirement.
*  The Microsoft Project import class still uses the php4 style of XML parsing. It should be updated to use SimpleXML or something similar to speed processing and reduce memory use.

## Updated in 4.2:

*  Added numerous notes on areas to cleanup & simplify in future versions;
*  Applied proper theming to all form fields;
*  Fixed the Contact lookups to work more often;
*  Fixed the Task Assignees to allow existing users (not just newly imported users) to be assigned to Tasks;
*  Fixed the Task Assignees to respect permissions and limit the selection only to Users who can actually view Tasks;
*  Fixed the "View the project here" link;
*  Fixed the Work Breakdown Structure id for the WBS Pro file format;
*  Refactored the flow of the module to the 'proper' structure now specified in the web2project Module Building Guide;
*  Refactored the post-upload step to a clean redirect with the preview;
*  Removed the references to the raw $_FILES;
*  Updated the class structures to standardize look & feel;
*  Updated the deprecated DBQuery to the new w2p_Database_Query class;
*  Updated the project/task processing to be timezone aware;
*  Updated the status messages to use the AppUI status messages instead of echo;

## Updated in 4.1:

*  Fixed a major issue where dependencies were not properly imported or sometimes at all.
*  Added a tweak to the MS Project processor to make sure the project title is retrieved properly.
*  Fixed an issue where the Project Start Date was not set properly.
*  Tweaked the import process so that if there's no Company match, it default's to the current User's Company. This resolves the issue where a Project could accidentally get assigned to no Company and therefore disappear due to permissions.
*  Changed the licensing to be in accordance with the the coming web2project shift.

## Updated in 4.0:

*  Added some hr tags and lined up some input boxes to make the UI cleaner.
*  Updated the module structure (paths and names) to use the core web2project autoloader and simplify the includes code.
*  Refactored much of the Project/Task/Contact creation code into its proper class methods instead of raw DBQuery calls. Updated those calls to use the object validation results. Further moved this code into the base CImporter class to reduce duplication.
*  Applied some major patches from Alain Picard to handle larger and larger file sizes. In most cases, this is a 20-25% improvement, but some imports can get as much as an 80% improvement in processing time.
  * ~95 Tasks process in   ~4 seconds using  12MB at peak;
  * ~450 Tasks process in  ~50 seconds using  15MB at peak;
  * ~1100 Tasks process in ~180 seconds using 380MB at peak;

## Install

1.  Previous installations of this module can simply be removed via the System Admin -> View Modules screen.
1.  To install this module, please follow the standard module installation procedure.
1.  Download the latest version from [Github](https://github.com/web2project/modules-importers) and unzip the file. Take the existing directory (called: importers) and move/upload it into your web2project/modules directory.
1.  Select to System Admin -> View Modules and you should see "Project Importer" near the bottom of the list.
1.  On the "importer" row, select "install".  The screen should refresh and the line should become "Project Importer".  Now select "hidden" and then "disabled" to make it display in your module navigation.
1.  You should be able to order it within the navigation like any other module.

## Usage

1.  Within Microsoft Project, open your project as usual.  In the File Menu, select "Save AS ...".  Under file-type, choose "xml" and save your file.
1.  Within Web2project, select "Project Import" from your module navigation.
1.  Select "Browse", select the file you saved in Step 1, and select "Import Data".
1.  Your screen should now show a summary of the imported project with all the relevant Company, Project, Task, and Assignment information.
1.  If a matching Company was found, the Importer will automatically assign it.  Alternatively, if there was no matching Company, you can choose one or allow the Importer to create a new one.
1.  Input a Project Name.  If the imported name is already in use, the module will note it here.
1.  Assign the usernames who match your resources.  Any users not mapped to current Web2project users will automatically be created in an Inactive state.
1.  Adjust the task assigments as necessary.  By default, the percent allocations from the MS Project file will be used.
1.  Select "import" below.

### Development Status

* v4.1 Update Release: 2010 September
* v4.0 Major  Release: 2010 April
* v3.2 Update Release: 2009 October
* <s>v3.1 Update Release: 2009 September</s>
* v3.0 Major  Release: 2008 September
* *All versions prior to v3.0 are only compatible with dotProject - http://docs.dotproject.net/index.php?title=Project_Importer*
* v2.0 Major  Release: 2007 October (requires PHP5 and dotProject v2.1 rc1 or above)
* v1.5 Update Release: 2007 July (last PHP4-compatible release)
* v1.4 Update Release: 2007 May
* v1.2 Update Release: 2006 October (last dotProject 2.0.x-compatible release)
* v1.1 Update Release: 2006 September
* v1.0 Initial Release: 2005 December

If you find this module particularly useful and would like to express
gratitude or seek additional development, please do not hesitate to contact
CaseySoftware, LLC via webmaster@caseysoftware.com

Microsoft Project is an application owned by Microsoft Corporation.  All Rights Reserved.
