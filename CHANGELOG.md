# Change Log


Updated in 3.2:

*  Fixed a bug in import where it simply wasn't importing Projects unless you had some odd combination of permissions... now it's be simplified to just requiring the Project:Add permission;
*  Converted a bunch of the raw DBQuery calls to calls on the proper objects, this doesn't do anything with the results of the object validation, but that's not formally in Web2project until v1.2 anyway;

Updated in 3.1:

*  Simplified the permissions checking to only check for Project Add privileges;
*  Removed the poorly implemented translation files to take advantage of the reworked translation handler in Web2project v1.1;
*  Simplified the View to use a case statement instead of nested if's.  The code isn't shorter, but it's quite a bit clearer;
*  General clean up to use core Web2project functionality as opposed to using custom code, it isn't complete;

Fixed in 3.0:

*  Updated the Project Importer to support Web2project.  In the process, backwards compatibility with dotProject was lost.  This is a known condition and is not considered an issue or bug, it is a design decision and will not be reversed or reconsidered.
*  Simplified the class structure and encapsulated more of the type-specific details away from the user and the BaseImporter class.
*  Converted some of the raw SQL statements to use the DBQuery class.

Fixed in 2.2:

*  Added a contribution from Wellison da Rocha Pereira to allow for importing of WBS Gant Chart Pro v4.4 files.
*  Added a contribution from Richard Siomporas to allow the User to choose whether or not to import Users from the imported file.
*  Found and resolved an issue where it was possible to assign a project to Company 0 which would prevent all users from accessing the project in any way.

Fixed in 2.1:

*  Fixed an issue with the task_type which was only being set consistently for dynamic tasks.  This would cause some tasks not to display in the Project View.
*  If you attempt to import a project and there is already one with an identical name, the Importer will automatically delete the existing tasks and import the new tasks into the existing project.  A warning will appear to the user during the preview stage.

Fixed in 2.0:

*  Fixed the parent/child relationship error.  In the v1.5 release, a bug was introduced which could cause parent/child relationships to be lost.
*  Fixed the User case mismatch error.  Previously, when the Importer created Users, it would use the same capitalization from the Microsoft Project file's Resources.  Unfortunately, this often created sitatuations wherea a new User named "Keith" was created while "keith" was an existing User.  The Importer now makes all usernames lowercase to match the rest of the system.
*  Improved overall memory management.  Previously, with 64M allocated to PHP, approximately 100 tasks in a single project plan could be imported.  With the new memory management, 64M has proven sufficient to import 450+ tasks.
*  Created a simple class hierarchy.  Previously, only a single preview() and import() could be supported.  By creating a simple class structure, the goal is to more easily support additional filetypes in future releases.
*  Adjusted the install() and remove() functions in the setup procedure to automatically attempt to install the translation files (English and German currently) to their proper locations within dotProject.  This ensures that all language changes via the Translation Management are reflected.

Fixed in 1.5:

*  Fixed the duration rounding error.  Previously tasks with fractional hour durations were being rounded down to the nearest hour.  Now the actual value is preserved.
*  Fixed the character cutoff issue.  Due to how PHP4 handles XML parsing and cdata blocks, special characters were triggering the parser to treat the single cdata node as multiple node therefore causing only the last of the text segments to be imported.
*  Various raw SQL updates were replaced with their DBQuery equivalents.

Fixed in 1.4:

*  Tweaked the resource import.  Previously there was no way to completely remove a person.  Changing a user's assignment to zero removes them from the task completely.
*  Fixed the resource import.  Previously, if a task had no one assigned the import process would generate an error.  It now checks the array size before attempting to iterate over it.
*  Added the ability to chose a Project Status during import.
*  Added the ability to chose a Project Owner during import, defaults to the user doing the import.
*  Modified the preview screen to warn the user if a username is below the minimum length set in their dotProject configuration.
*  Modified the preview screen to warn the user about permissions if the module is about to create a new Company.
*  Modified the import functionality to use the properly scrubbed parameters, etc via the core dPgetParam functions.
*  Cleaned up the code to better match the coding standards in use by dotProject (PEAR).
*  Added the security sentinal from core dotProject to prevent unauthorized access via the filesystem.  This breaks compatibility with all dotProject releases prior to 2.1-rc1.
*  Renamed the module from the "Microsoft Project Importer" to simply "Project Importer".  This is to avoid any potential legal issues and acknowledging the planned direction of the module.

Fixed in 1.3:

*  All user assignments are now calculated from the values in the Microsoft Project file itself.  This insight was shared by the highly esteemed Tim Millhouse of Digilore, Inc and implemented by D. Keith Casey, Jr. of CaseySoftware, LLC.