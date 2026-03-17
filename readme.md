## Schedule Manager

Sup yall, I'm Finn 
I'll guide you through how to run and configure this project properly.

## Before You Start

Make a **copy of the entire project** before modifying anything.
This way you can restore the original files if something breaks.

## Requirements

To run this project you need to install:

* XAMPP Control Panel
* MySQL Workbench

After installing **XAMPP**, start the following services:

* Apache
* MySQL

## Project Setup

1. Open the project folder.
2. Go to the `config` directory.
3. Open the file:

```
config.php
```

This file contains the configuration for the whole system.

## Database Configuration

If the database does not connect properly, check the **port configuration**.

Default MySQL port:

```
3306
```

If that port does not work, try changing it to:

```
3307
```

You can also modify the database settings in the configuration file if needed.

## Notes

* Do not modify important files unless you understand what they do.
* Always keep a backup of the project before making changes.

---

More detailed setup instructions will be added soon.

Also, you need to see the files in /api/v1/../.. there all the information about the backend related to stats, acticity, etc

**// UPDATE SOON //**
