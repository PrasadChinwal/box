# UIS ITS Box

A wrapper to integrate Box Api's to a Laravel Application.

###### Developers Guide: [https://developer.box.com](https://developer.box.com).

## Installation
- `composer require prasadchinwal/box`
- Run `php artisan vendor:publish` and publish the config file.
- Edit the `config/box.php` file to configure your settings. To know more about configuration visit [https://developer.box.com/guides/](https://developer.box.com/guides/)


## Usage:

---  

### **File Api**

---

- Get File Info: [Documentation](https://developer.box.com/reference/get-files-id/)
  Retrieves the details about a file.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::file()->whereId('1234')->info();
    ```

- Download File: [Documentation](https://developer.box.com/reference/get-files-id-content/)
  Returns the contents of a file in binary format.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::file()->whereId('1234')->downloadFile();
    ```

- Get Download File URL: [Documentation](https://developer.box.com/guides/downloads/get-url/)
  Returns the download url of a file as a string.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::file()->whereId('1234')->getDownloadUrl();
    ```

- Create Shared Link for File: [Documentation](https://developer.box.com/reference/put-files-id--add-shared-link/)
  Adds a shared link to a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    $attributes = [
        'shared_link' => [
            'access' => 'company',
            'permissions' => [
                'can_download' => true,
                'can_edit' => true,
            ]
        ]
    ];
    Box::file()->whereId('1234')->createSharedLink(attributes: $attributes);
    ```

- Find file from Shared Link: [Documentation](https://developer.box.com/reference/get-shared-items/)
  Returns the file represented by a shared link.
  A shared file can be represented by a shared link, which can originate within the current enterprise or within another.
  This endpoint allows an application to retrieve information about a shared file when only given a shared link.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    $sharedLink = 'https://my.box.com/s/xasddyuejhkljawd';
    Box::file()->whereLink($sharedLink)->find();
    ```

- Get Shared Link for file: [Documentation](https://developer.box.com/reference/get-files-id--get-shared-link/)
  Gets the information for a shared link on a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1234')->getSharedLink();
    ```

- Get thumbnail for File: [Documentation](https://developer.box.com/reference/get-files-id-thumbnail-id/)
  Retrieves a thumbnail, or smaller image representation, of a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
     Box::file()->whereId('1234')->thumbnail(extension: '.jpg');
    ```

- Copy File: [Documentation](https://developer.box.com/reference/post-files-id-copy/)
  Copies the file from one location to another.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    $attributes = [
        'name' => 'TestFile.pdf', // An optional new name for the copied file.
        'parent' => [
            'id' => '4321' // The ID of folder to copy the file to.
        ],
        'version' => null // An optional ID of the specific file version to copy.
    ];
    Box::file()->whereId('1234')->copy(attributes: $attributes);
    ```

- Update File: [Documentation](https://developer.box.com/reference/put-files-id/)
  Updates the existing file with attributes provided.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    $attributes = [
        'name' => 'Test.pdf',  // An optional different name for the file. This can be used to rename the file.
        'description' => 'Testing update api.', //The description for a file. This can be seen in the right-hand sidebar panel when viewing a file in the Box web app. Additionally, this index is used in the search index of the file, allowing users to find the file by the content in the description.
        'disposition_at' => '2023-12-12T10:53:43-08:00', // The retention expiration timestamp for the given file. This date cannot be shortened once set on a file.
        'lock' => [ // Defines a lock on an item. This prevents the item from being moved, renamed, or otherwise changed by anyone other than the user who created the lock.
            'access' => 'lock',  // The type of this object. Value is always lock
            'expiration_at' => '',  // Defines the time at which the lock expires.
            'is_download_prevented' => false,  // Defines if the file can be downloaded while it is locked.
        ],
        'parent' => [  // An optional new parent folder for the file. This can be used to move the file to a new folder.
            'id' => ''  // The ID of parent item
        ],
        'permissions' => [  // Defines who can download a file.
            'can_download' => 'open'  // Defines who is allowed to download this file. The possible values are either open for everyone or company for the other members of the user's enterprise.
        ],
        'shared_link' => [  // Defines a shared link for a file. Set this to null to remove the shared link.
            'access' => '',  // The level of access for the shared link. This can be restricted to anyone with the link (open), only people within the company (company) and only those who have been invited to the folder (collaborators).
            'password' => '',  // The password required to access the shared link. Set the password to null to remove it. Passwords must now be at least eight characters long and include a number, upper case letter, or a non-numeric or non-alphabetic character. A password can only be set when access is set to open.
            'permissions' => [  //
                'can_download' => '',  // If the shared link allows for downloading of files. This can only be set when access is set to open or company.
                'unshared_at' => '',  // The timestamp at which this shared link will expire. This field can only be set by users with paid accounts.
                'vanity_name' => '',  // Defines a custom vanity name to use in the shared link URL, for example https://app.box.com/v/my-shared-link.
            ]
        ]
    ];
    Box::file()->whereId(fileId: '1234')->update(attributes: $attributes);
    ```

- Delete File: [Documentation](https://developer.box.com/reference/delete-files-id/)  
  Deletes a file, either permanently or by moving it to the trash.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1234')->delete();
    ```

- Upload File: [Documentation](https://developer.box.com/reference/post-files-content/)  
  Uploads a small file to Box. For file sizes over 50MB we recommend using the Chunk Upload APIs.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    $attributes = [
        'attributes' => json_encode(['name' => 'New_Test.pdf', 'parent' => ['id' => '4321']]),
    ];  // For full list of available options please see the docs.
    $filePath = storage_path('app/file.pdf');
    Box::file()->create(filepath: $filePath, filename: 'My_New_File.pdf',  attributes: $attributes);
    ``` 

- File Versions: [Documentation](https://developer.box.com/reference/get-files-id-versions/)  
  Retrieve a list of the past versions for a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1234')->versions();
    ``` 

- Get File Watermark: [Documentation](https://developer.box.com/reference/get-files-id-watermark/)  
  Retrieve the watermark for a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1234')->getWatermark();
    ```

- Create File Watermark: [Documentation](https://developer.box.com/reference/put-files-id-watermark/)  
  Applies or update a watermark on a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1234')->createWatermark();
    ``` 

- Remove File Watermark: [Documentation](https://developer.box.com/reference/delete-files-id-watermark/)  
  Removes the watermark from a file.
    ```php
    use PrasadChinwal\Box\Facades\BoxFile;
    Box::file()->whereId('1248628118855')->removeWatermark();
    ``` 

---

### Folder Api

---

- Get Folder Information: [Documentation](https://developer.box.com/reference/get-folders-id/)  
  Retrieves details for a folder, including the first 100 entries in the folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::folder()->whereId('4321')->info();
    ``` 

- [Get Folder Items](https://developer.box.com/reference/get-folders-id-items/)    
  Deletes a folder, either permanently or by moving it to the trash.
    ```php
        Box::folder()->whereId('4321')->items();
    ```

- [Create Folder](https://developer.box.com/reference/post-folders/)  
  Creates a new empty folder within the specified parent folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'folder_upload_email' => [  // Setting this object enables the upload email address. This email address can be used by users to directly upload files directly to the folder via email.
            'access' => ''  // When this parameter has been set, users can email files to the email address that has been automatically created for this folder. Value is one of open,collaborators
        ],
        'name' => '',  // The name for the new folder. max length 255
        'parent' => [  // The parent folder to create the new folder within
            'id' => ''  // The ID of parent folder
        ],
        'sync_state' => ''  // Specifies whether a folder should be synced to a user's device or not. This is used by Box Sync (discontinued) and is not used by Box DriveSpecifies whether a folder should be synced to a user's device or not. This is used by Box Sync (discontinued) and is not used by Box Drive. Value is one of synced,not_synced,partially_synced
    ];
    Box::folder()->whereId('4321')->create(attributes: $attributes);
    ```

- [Update Folder](https://developer.box.com/reference/put-folders-id/)  
  Updates a folder. This can be also be used to move the folder, create shared links, update collaborations, and more.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'can_non_owners_invite' => [  // Specifies if users who are not the owner of the folder can invite new collaborators to the folder.
            'can_non_owners_view_collaborators' => '',  // Restricts collaborators who are not the owner of this folder from viewing other collaborations on this folder. It also restricts non-owners from inviting new collaborators. It also restricts non-owners from inviting new collaborators.
        ],
        'collections' => [  // An array of collections to make this folder a member of. Currently we only support the favorites collection.
            'id' => '',  // The unique identifier for this object
            'type' => '', // The type for this object
        ],
        'description' => '',  // The optional description of this folder
        'folder_upload_email' => [  // Setting this object enables the upload email address. This email address can be used by users to directly upload files directly to the folder via email.
            'access' => '',  // When this parameter has been set, users can email files to the email address that has been automatically created for this folder. To create an email address, set this property either when creating or updating the folder.
        ],
        'is_collaboration_restricted_to_enterprise' => '',  // Specifies if new invites to this folder are restricted to users within the enterprise. This does not affect existing collaborations.
        'name' => '',  // The optional new name for this folder.
        'parent' => [  // The parent folder to create the new folder within
            'id' => '212346363047'  // The ID of parent folder
        ],
        'shared_link' => [  // Enables the creation of a shared link for a folder.
            'access' => '',  // The level of access for the shared link. This can be restricted to anyone with the link (open), only people within the company (company) and only those who have been invited to the folder (collaborators). If not set, this field defaults to the access level specified by the enterprise admin. To create a shared link with this default setting pass the shared_link object with no access field, for example { 'shared_link': {} }.
            'password' => '',  // The password required to access the shared link. Set the password to null to remove it. Passwords must now be at least eight characters long and include a number, upper case letter, or a non-numeric or non-alphabetic character. A password can only be set when access is set to open.
            'permissions' => [
                'can_download' => '',  // If the shared link allows for downloading of files. This can only be set when access is set to open or company.
                'unshared_at' => '',  // The timestamp at which this shared link will expire. This field can only be set by users with paid accounts.
                'vanity_name' => '',  // Defines a custom vanity name to use in the shared link URL, for example https://app.box.com/v/my-shared-link.Custom URLs should not be used when sharing sensitive content as vanity URLs are a lot easier to guess than regular shared links.
            ],
        ],
        'sync_state' => '',  // Specifies whether a folder should be synced to a user's device or not. This is used by Box Sync (discontinued) and is not used by Box Drive. Value is one of synced,not_synced,partially_synced
        'tags' => [''],  // The tags for this item. These tags are shown in the Box web app and mobile apps next to an item. To add or remove a tag, retrieve the item's current tags, modify them, and then update this field. There is a limit of 100 tags per item, and 10,000 unique tags per enterprise. 
    ];
    Box::folder()->whereId('4321')->update(attributes: $attributes);
    ```

- Copy Folder: [Documentation](https://developer.box.com/reference/post-folders-id-copy/)  
  Creates a copy of a folder within a destination folder. The original folder will not be changed.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'name' => 'Test',  // The name for the new folder. max length 255
        'parent' => [  // The parent folder to create the new folder within
            'id' => '54321'  // The ID of parent folder
        ],
    ];
    Box::folder()->whereId('4321')->copy(attributes: $attributes);
    ```

- Delete Folder: [Documentation](https://developer.box.com/reference/delete-folders-id/)    
  Deletes a folder, either permanently or by moving it to the trash.
    ```php
        Box::folder()->whereId('4321')->delete(recursive: true);
    ```

- Find Folder from shared link: [Documentation](https://developer.box.com/reference/get-shared-items--folders/)    
  Return the folder represented by a shared link.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::folder()->whereLink('https://test.box.com/s/dad4dasdcddasd')->find();
    ```

- Get shared link: [Documentation](https://developer.box.com/reference/get-folders-id--get-shared-link/)  
  Gets the information for a shared link on a folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::folder()->whereId('4321')->getSharedLink();
    ```

- Creare shared link: [Documentation](https://developer.box.com/reference/put-folders-id--add-shared-link/)
  Adds a shared link to a folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'shared_link' => [
            'access' => 'company',
            'permissions' => [
                'can_download' => true,
            ]
        ]
    ];
    Box::folder()->whereId('4321')->createSharedLink(attributes: $attributes);
    ```

- Get Locks: [Documentation](https://developer.box.com/reference/get-folder-locks/)
  Retrieves folder lock details for a given folder. You must be authenticated as the owner or co-owner of the folder to use this endpoint.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::folder()->whereId('4321')->getLocks();
    ```

- Create Lock: [Documentation](https://developer.box.com/reference/post-folder-locks/)
  Creates a folder lock on a folder, preventing it from being moved and/or deleted.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'folder' => [
            'type' => '',
            'id' => '4321'
        ],
        'locked_operations' => [
            'move' => true,
            'delete' => true,
        ]
    ];
    Box::folder()->lock(attributes: $attributes);
    ```

- Remove Lock: [Documentation](https://developer.box.com/reference/delete-folder-locks-id/)
  Deletes a folder lock on a given folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::folder()->unlock(lockid: '0983');
    ```

---

### Collaborations Api

---

- Get Collaboration: [Documentation](https://developer.box.com/reference/get-collaborations-id/)
  Deletes a folder lock on a given folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::whereId('45678')->get();
    ```

- Create Collaboration: [Documentation](https://developer.box.com/reference/post-collaborations/)
  Adds a collaboration for a single user or a single group to a file or folder.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'item' => [
            'type' => 'folder',
            'id' => '4321',
        ],
        'accessible_by' => [
            'type' => 'user',
            'login' => 'xyz@abc.edu'
        ],
        'role' => 'editor'
    ];
     Box::create(attributes: $attributes);
    ```

- Update Collaboration: [Documentation](https://developer.box.com/reference/put-collaborations-id/)
  Updates a collaboration. Can be used to change the owner of an item, or to accept collaboration invites.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    $attributes = [
        'can_view_path' => true,
        'status' => 'accepted',  // pending / accepted / rejected
        'role' => 'editor'  // editor / viewer / previewer / uploader / previewer / uploader / viewer / uploader / co-owner / owner
    ];
    Box::whereId('1111')->update(attributes: $attributes);
    ``` 

- Delete Collaboration: [Documentation](https://developer.box.com/reference/delete-collaborations-id/)
  Deletes a single collaboration.
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::collaboration()->whereId('1111')->delete();
    ``` 
---

### User Api

---

- Get User: [Documentation](https://developer.box.com/reference/get-users-id/)
  Get User 
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::user()->get();
    ```

- Get User: [Documentation](https://developer.box.com/reference/get-users-id/)
  Get User
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::user()->whereId('3477983675')->memberships();
    ```

- Transfer User Folder: [Documentation](https://developer.box.com/reference/put-users-id-folders-0/#param-owned_by-id)
  Get User
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::user()->whereId('3477983675')->transfer(toUser: 'The ID of the user who the folder will be transferred to', notify: false);
    ```

- Delete User: [Documentation](https://developer.box.com/reference/delete-users-id/)
  Get User
    ```php
    use PrasadChinwal\Box\Facades\Box;
    Box::user()->whereId('3477983675')->delete(force: false, notify: false);
    ```
