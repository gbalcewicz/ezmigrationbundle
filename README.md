# Kaliop eZ-Migration Bundle

This bundle makes it easy to handle eZPlatform / eZPublish 5 content upgrades/migrations.

It is inspired by the DoctrineMigrationsBundle ( http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html )


## Installation

In either `require` or `require-dev` at the end of the bundle list add:

    "kaliop/ezmigrationbundle": "~1.0"

Save composer.json and run

    composer update --dev kaliop/migration

This will install the bundle and all its dependencies.

Please make sure that you have the bundle registered in the kernel as well. Check `ezpublish/EzPublishKernel.php`

The `registerBundles` method should look similar to:

    public function registerBundles()
    {
        $bundles = array(
            ... more stuff here ...
            new Kaliop\eZMigrationBundle\EzMigrationBundle()
        );
    }

### Checking that the bundle is installed correctly

If you run `php ezpublish/console` you should see 3 new commands in the list:

    kaliop
      kaliop:migration:generate
      kaliop:migration:status
      kaliop:migration:update

This indicates that the bundle has been installed and registered correctly.


## Updating the bundle

To get the latest version, you can update the bundle using `composer`

    composer update kaliop/ezmigrationbundle


## Running tests

The bundle has both unit tests and BDD features.

To run the unit tests just point PHPUnit to the bundle directory:

    bin/phpunit vendor/kaliop/ezmigrationbundle

The Behat instructions are left here for future reference when we get it working correctly with eZ Publish 5.
To run the BDD test with Behat:

    bin/behat @KaliopBundleMigrationBundle


## Usage

All commands accept the standard Symfony/eZ publish 5 options, although some of them might not have any effect on the
command's execution.

### Generating new empty migration files

The bundle provides a command to easily generate a new blank migration definition file in a specific bundle.

For example:

    php ezpublish/console kaliop:migration:generate --type=yml KaliopKBaseSimpleContentTypesBundle

The above command will place a new yml skeleton file in the `MigrationVersions` directory of the KaliopKBaseSimpleContentTypes bundle.

If the directory does not exists then the command will fail with an error message.
If the command is successful it will create a new yml file named with the following pattern: YYYYMMDDHHMMSS_place_holder.yml

(the contents of the skeleton Yaml file are stored in the GenerateCommand::$ymlTemplate and the skeleton PHP file's
contents in GenerateCommand::$phpTemplate)

### Listing all migrations and their status

To see all the migrations in the system and whether they have been applied or not simply run the status command in your
eZ Publish 5 root directory:

    php ezpublish/console kaliop:migration:status

The list of migrations which have been already applied is stored in the database, in a table named `kaliop_versions`.
The bundle will automatically create the table if needed.

### Applying migrations

To apply all available migrations run the update command in your eZ Publish 5 root directory:

     php ezpublish/console kaliop:migration:update

### Editing migration files

So far so good, but what actions exactly can be defined in a migration file?

The docs describing all supported parameters are in the [DSL Language description](Resources/doc/DSL/README.md)

Specific topics are covered below

#### Importing binary files

To import binary files like images into attributes (ezimage, ezbinaryfile fields) the attribute needs to be defined as
a complex type to tell the system to handle it differently.

Below is a snippet of how a simple/primitive field type is defined in a content creation Yaml definition:

    attributes:
        - title: 'Example title'

To define a complex attribute we need to indicate the type of the attribute and then provide the required data fields for
the attribute.

Below is an example snippet for ezimage:

    attributes:
        - image:
            type: ezimage
            path: /path/to/the/image.jpg
            alt_text: 'Example alt text'

Images __need__ to be placed in the `MigrationVersions/images` folder.
Binary files __need__ to be placed in the `MigrationVersions/files` folder.

The paths to files/images in the definition are relative paths from the MigrationVersions/images or MigrationVersions/files folders.
For example using the path from the snippet above the system would look for the image file in
`MigrationVersions/images/path/to/the/image.jpg` in the bundle's directory.

Please see the `ManageContent.yml` DSL definition file for more information in the `Resources/doc/DSL` folder.

#### Using references in your migration files

The Yaml definitions support setting references of values of certain attributes, that you can retrieve in the following
instructions.
For example you could set a reference to a folder's location id and then use that as the parent location when creating
articles in that folder.

See the following example on using references. The example creates a new content type in the system and sets a reference
to it's id.
The second set of instructions adds a new policy to the editor role to allow editors to create objects of the new content
type under the node with id 2.

    -
        mode: create
        type: content_type
        content_type_group: 1
        name: Section Page
        identifier: section_page
        name_pattern: <title>
        is_container: true
        attributes:
            -
                type: ezstring
                name: Title
                identifier: title
                required: true
                searchable: true
                info-collector: false
                disable-translation: false
                default-value: New section page
            -
                type: ezxmltext
                name: Body
                identifier: body
                required: false
                searchable: true
                info-collector: false
                disable-translation: false
        references:
            -
                identifier: section_page_class
                attribute: content_type_id

    -
        mode: update
        type: role
        name: Editor
        policies:
            add:
                -
                    module: content
                    function: create
                    limitation:
                        -
                            type: Node
                            value: [2]
                        -
                            type: Class
                            value: [reference:section_page_class]


To set the reference we used the `references` section of the content type DSL. As you can see we set a reference named
`section_page_class` to store the content type id.
In the update role action we retrieved the value of the reference by using the `reference:section_page_class`.

To tell the system that you want to use a previously stored reference you need to prefix the reference name with the string
`reference:`. This tells the system to look in the stored references for attributes that support this.

Currently you can use references to store the following values:

-   content
    -   content id
    -   location id
-   content type
    -   content type id
    -   content type identifier
-   location
    -   location id
-   role
    -   role id
    -   role identifier
-   user group
    -   user group id
-   user
    -   user id

You can use references to set the following values:

-   content
    -   content type identifier
    -   parent location id
-   location
    -   object id (The id of the object who's locations you want to manage)
    -   remote id (The remote id of the object who's locations you want to manage)
    -   parent location id (The list of parent locations where the new locations need to be created)
    -   location id to swap the current location with (Only on update actions)
-   role
    -   limitation values
-   user group
    - parent user group id
-   user
    - user group id

For more information please see the DSL definitions in the `Resources/doc/DSL` folder.

#### References in the XML for the eZXMLText Field

To tell the system to look for references in the xml that is used to populate ezxmltext type fields the Yaml definition
will need to use the definition used for defining complex attributes.
Please see the importing binary files section above on how to define complex data type handling for an attribute.

Below is an example snippet showing how to define references for ezxmltext.

    attributes:
        - description:
            type: ezxmltext
            content: '<section><paragraph><embed view="embed" size="medium" object_id="[reference:test_image]" /></paragraph></section>'
