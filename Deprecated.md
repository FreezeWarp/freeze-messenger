The following is a list of items to be removed in FIMv4:

# Database #
## Vanilla Logins ##
The current vanilla login system is at best placeholder. It may be improved in the future, but should not be expected to retain compatibility with previous iterations.

# APIs #
All APIs in FIMv3 are largely final. Few changes should be expected, however:
  * In FIMv4, the "errDesc" node will be removed from all APIs.

# Functions #
  * The FIMv3 database API is still in its infancy. It will see large changes when new drivers become supported. However, the general syntax should not change, so most or all uses of the database class should largely remain supported in future versions.
  * All FIMv3 parser functions should be final. They will not change in the near future.
  * All FIMv3 general functions should be final. They will not change in the near future.