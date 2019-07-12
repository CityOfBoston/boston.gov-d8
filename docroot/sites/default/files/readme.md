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
    |   ├── columns                    * Paragraph columns 
    |   |   └── [dated]                 - migrated files
    |   ├── event                      * Paragraph event
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   ├── hero_image                 * Paragraph hero_image 
    |   |   └── [dated]                 - migrated files
    |   ├── how_to                     * Paragraph how_to 
    |   |   └── intro_images            - from field_intro_image
    |   ├── icons                      * Holds svg icon files 
    |   |   ├── department              
    |   |   |   └── [dated]             - migrated files
    |   |   ├── emergency                  
    |   |   |   └── [dated]             - migrated files
    |   |   ├── feature                  
    |   |   |   └── [dated]             - migrated files
    |   |   ├── fyi                      
    |   |   |   └── [dated]             - migrated files
    |   |   ├── site_alert                   
    |   |   |   └── [dated]             - migrated files
    |   |   ├── status                   
    |   |   |   └── [dated]             - migrated files
    |   |   └── transactions             
    |   |       └── [dated]             - migrated files
    |   ├── library                    * Holds photographic images 
    |   |   └── photos                   
    |   |       └── [dated]             - migrated files
    |   ├── listing_page               * Paragraph listing_page 
    |   |   └── intro_images            - from field_intro_image
    |   ├── person_profile             * Paragraph person_profile
    |   |   └── photos                  - from field_photos
    |   ├── place_profile              * Paragraph place_profile
    |   |   └── intro_images            - from field_intro_image
    |   ├── post                       * Paragraph post
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   ├── program                    * Paragraph program
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── logo                    - from field_logo
    |   ├── quote_person               * Paragraph quote_person
    |   |   └── photos                  - from field_photos
    |   ├── tabbed                     * Paragraph tabbed
    |   |   └── intro_images            - from field_intro_image
    |   ├── topic                      * Paragraph topic
    |   |   ├── intro_images            - from field_intro_image
    |   |   └── thumbnails              - from field_thumbnail
    |   ├── unk                        * Unknown origin (to migration)
    |   |   ├── intro_images            
    |   |   └── thumbnails              
    |   └── video                      * Paragraph video
    |       └── [dated]                 - migrated files
    ├── private                         PRIVATE FILES
    └── unk                             UNKNOWN ORIGIN (to migration) FILES
    
```     
Image styles used:

   
|Entity | Field | Target Def | Style |   
|:-----|:-----|-----:|:-----|
| **Images** |
| node:department_profile | field_icon | 56x56/++ - 200KB | square_icon_56px |
| node:site_alert | field_icon | 56x56/++ - 200KB | square_icon_56px |
| node:status_item | field_icon | 65x65/++ - 200KB | square_icon_65px |
| para:fyi | field_icon | 56x56/++ 200KB | square_icon_56px |
| para:signup_emergency_alerts | field_icon | n/a svg  | n/a svg |
| para:transactions | field_icon | 180x100/++ - 2MB  | transaction_icon_180x100 |
| tax:features | field_icon | svg  | square_icon_56px |
| node:event | field_intro_image | 1440x396/++ 8 MB | Resp: intro_image_fields |
| node:how_to | field_intro_image | 1440x396/++ 8 MB  | Resp: intro_image_fields |
| node:listing_page | field_intro_image | 1440x396/++ 8MB  | Resp: intro_image_fields |
| node:place_profile | field_intro_image | 1440x396/++ 8MB  | Resp: intro_image_fields |
| node:post | field_intro_image | 1440x396/++ 8MB | Resp: intro_image_fields |
| node:program_i_p | field_intro_image | 1440x396/++ 8MB  | Resp: intro_image_fields |
| node:tabbed_content | field_intro_image | 1440x396/*++ 8MB  | Resp: intro_image_fields |
| node:topic_page | field_intro_image |   |  |
| node:event | field_thumbnail | 525x230/++ 8 MB | Resp: thumbnail_event |
| node:post | field_thumbnail |   |  |
| node:topic_page | field_thumbnail |   |  |
| node:person_profile | field_person_photo |   |  |
| node:quote | field_person_photo |   |  |
| node:program_i_p | field_program_logo |   |  |
| para:columns | field_image |   |  |
| para:hero_image | field_image |   |  |
| para:map | field_image |   |  |
| para:photo | field_image |   |  |
| para:video | field_image |   |  |
| user | user_picture |   |  |
| media.image | image |   |  |
| **Files** |
| media.document | field_document |   |  |
| node:procurement | field_document |   |  |
| para:document | field_document |   |  |

min/max
++ = not specified (unlimited)

Responsive sets use the following styles:
intro_image_fields:
- intro_image_xxx
thumbnail_event:
- thumbnail_event_square(190*190)
- thumbnail_event_rectangle(575x230)