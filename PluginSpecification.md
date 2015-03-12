**Plugins are still in rough-draft stage, and will not be implemented until FIMv4 or later. What follows is a brainstorm.**

# Introduction #
The following is the specification used for all interfaces, plugins, and language packs that can be used by FreezeMessenger. All documents must be XML (alternative formats simply serve no purpose here), and should be UTF-8 encoded. Support for UTF-16 may come in FIMv5.

## Reading This Document ##

Each section contains the structure for an XML document that can be used with FreezeMessenger to make core modifications. Each contains at least two subsections: "Format" and "Examples", as well as possible others, "Notes" and "Attributes". "Examples" shows an example of the XML document in action, while "Notes" details mostly unimportant characteristics about the XML document. "Format" is the tree structure of the XML document, and can be read as following:

  * root node  @attribute1=default @attribute2
    * child node @attribute1=default @attribute2
      * grandchild node @attribute1 #textValue

This would display as such in an actual XML document:
```
<root attribute1="default" attribute2>
  <child attribute1="default" attribute2>
    <grandchild attribute1="default">textValue</grandchild>
  </child>
</root>
```

Finally, the "Attributes" document explains in-depth the attributes shown under format.

# Database Schema #
Database Schema can be included in a separate file, or as a subnode in a plugin file.

## Format ##
  * dbSchema
    * database @name=CORE @charset=utf8
      * table @name @type=general @comment @merge
        * column @name @type=general @maxlen @restrict @comment @autoincrement
        * key @name @type @comment
    * definitions
      * bitfield @name @table
        * def @bit #definition
      * column @name @table
        * def @value #definition

## Attributes ##
  * database
    * @name - The database name (as a constant).
      * CORE - The standard database used.
      * INTEGRATION - The database used for login.
    * @charset - The charset of the database, if it should be created.
  * database -> table
    * @name
    * @type
      * general - The standard, permanent storage type (InnoDB for MySQL)
      * memory - The memory storage type, if if it exists.
    * @maxlen
    * @comment
  * database -> table -> column
  * database -> table -> key
    * @name - The column name. It can be comma-seperated if the key is applied to multiple columns.
    * @type - The key type.
      * primary
      * index
      * unique

## Notes ##
  * No definitions will overwrite existing tables or columns. Tables will be merged, and columns will cause the installation to fail.

## Examples ##
### Create a Table ###
```
<?xml version="1.0" encoding="utf-8"?>

<dbSchema version="3.0">
  <database name="__CORE__" charset="utf8">
    <table name="roomLists" type="general" comment="Lists of rooms that can be subscribed to by users.">
      <column name="listId" type="int" maxlen="6" autoincrement="true" comment="The idea of the list" />
      <column name="listName" type="string" maxlen="40" comment="The name of the list." />
      <column name="roomIds" type="string" maxlen="100" comment="A comma-seperated list of room IDs." />

      <key name="listId" type="primary" />
    </table>
  </database>
</dbSchema>
```

### Add Two Columns to the User Table ###
```
<?xml version="1.0" encoding="utf-8"?>

<dbSchema version="3.0">
  <database name="__CORE__" charset="utf8">
    <table name="users" merge="true">
      <column name="friends" type="string" maxlen="40" comment="A list of friend user IDs." />
      <column name="enemies" type="string" maxlen="40" comment="A list of friend user IDs." />

      <key name="listId" type="primary" />
    </table>
  </database>
</dbSchema>
```

# Database Data #
Database Data can be included in a seperate file, or as a subnode of a plugin file. It defines data that should be inserted into a database, and is structurally similar to dbSchema.

## Format ##
  * dbData @version
  * database
    * table @name
    * column @name @value

## Examples ##

### Insert a New User ###
```
<?xml version="1.0" encoding="utf-8"?>

<dbData version="3.0">
  <database>
    <table name="users">
      <column name="userId" value="1" />
      <column name="userName" value="Awesomeness" />
    </table>
  </database>
</dbData>
```

# Templates (Interface Modification) #
New templates can be added using TemplatePack files, which add templates to the database. In most cases, they should be included as a part of the addon or interface files listed below.

## Format ##
  * templates
    * template

## Examples ##


# LanguagePacks (Interface Modification) #
Addition langauges can be installed using LanguagePack files, which add phrases and languages to the database.

## Format ##
  * languagePack
    * metadata
    * phrases
      * phrase @name #phraseData

## Examples ##

# TemplateMods (Interface Modification) #
Template modifications can be made using TemplateMod files, which apply find+replace operations to existing templates.

## Format ##
  * templateMods
    * templateMod @template @vars
      * search
      * replace
      * prepend
      * append

## Examples ##

# HookMods (Core Modification) #
Code modifications can be made using CodeMod files, which add to the hooks database.

## Format ##
  * hookMods
    * hookMod

## Examples ##

# FileSets #
Files can be uploaded using FileSet files.

## Format ##
  * fileSet
    * file

## Examples ##

# AddOns and Interfaces (Core Modification) #
There are two types of "wrapper" files, which combine many of the above formats. The main difference is interfaces are not allowed to modify the core system, thus can not include dbData, dbSchema, templateMods, or codeMods. Of course, because they still enable fileSets (which allow for file uploads of any type), they can still in theory access all of those directives _unless_ a system administrator uses two separate servers for the interfaces and for the core system (which is encouraged, but not aided by FIMv3).

The structure below is for AddOns, but Interfaces simply remove the four aforementioned nodes, and changes the root node to "interface").

## Format ##
  * addon (or interface) @version @name
    * metadata
    * dbSchema
    * dbData
    * fileSets
    * codeMods
    * templates
    * phrases @languageCode @languageName
    * templateModPack

## Notes ##
  * The only node changed from its interpretation listed in the first several sections is phrases, which inherits languagePack's languageCode and languageName attributes. In theory, languagePack's phrases node can also use contain these properties, but doing so is discouraged.

## Examples ##