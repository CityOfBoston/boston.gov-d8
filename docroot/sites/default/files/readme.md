The file structure is as follows:
```
sites/default/files
    ├── file                           DOCUMENT FILES ROOT
    |   ├── [dated]
    |   └── document_files
    |       ├── [year]
    |       └── unk
    ├── embed                          RICH-TEXT EDITOR ROOT
    |   ├── [file-firstchar]
    |   └── file
    |       └── [dated]
    ├── img                            IMAGE ROOT FOLDER
    |   ├── [dated]                     - migrated files from public:// (root)
    |   └── columns                    * Paragraph columns 
    |   |   └── [dated]                 - migrated files
    |   └── event                      * Paragraph event
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   └── hero_image                 * Paragraph hero_image 
    |   |   └── [dated]                 - migrated files
    |   └── how_to                     * Paragraph how_to 
    |   |   └── intro_images            - from field_intro_image
    |   └── icons                      * Holds svg icon files 
    |   |   ├── department              
    |   |       └── [dated]             - migrated files
    |   |   ├── feature                  
    |   |       └── [dated]             - migrated files
    |   |   ├── fyi                      
    |   |       └── [dated]             - migrated files
    |   |   ├── status                   
    |   |       └── [dated]             - migrated files
    |   |   └── transactions             
    |   |       └── [dated]             - migrated files
    |   └── library                    * Holds photographic images 
    |   |   └── photos                   
    |   |       └── [dated]             - migrated files
    |   └── listing_page               * Paragraph listing_page 
    |   |   └── intro_images            - from field_intro_image
    |   └── person_profile             * Paragraph person_profile
    |   |   └── photos                  - from field_photos
    |   └── place_profile              * Paragraph place_profile
    |   |   └── intro_images            - from field_intro_image
    |   └── post                       * Paragraph post
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   └── program                    * Paragraph program
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── logo                    - from field_logo
    |   └── quote_person               * Paragraph quote_person
    |   |   └── photos                  - from field_photos
    |   └── tabbed                     * Paragraph tabbed
    |   |   └── intro_images            - from field_intro_image
    |   └── topic                      * Paragraph topic
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   └── unk                        * Unknown origin (to migration)
    |   |   ├── intro_images            
    |   |   └── thumbnails              
    |   └── video                      * Paragraph video
    |       └── [dated]                 - migrated files
    ├── private                         PRIVATE FILES
    └── unk                             UNKNOWN ORIGIN (to migration) FILES
    
```     