# Introduction #

If you change the directory or domain FreezeMessenger is hosted on, problems will occur with the internal "cURL" wrapper used by the Lite interface. When changing domains, please follow the below steps:

# To Fix #

  1. Open up the file "config.php".
  1. Find the configuration value for "$installUrl"
  1. Change it to the updated domain.

# Alternative Options #

(To be implemented.)

  1. This can be fixed automatically by going to /install/restore.php. However, this is NOT secure.