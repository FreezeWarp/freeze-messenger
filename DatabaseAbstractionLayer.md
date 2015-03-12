**Note: This document is a few early WIP.**

The Database Abstraction Layer used in FIMv3 accomplishes the task of allowing data to be expressed without reliance on SQL or similar database languages. At its creation, it was still largely an experiment, and so only works with MySQL. There are a number of limitations that exist to make things are secure or compatible as possible, but you must be aware of them:
  * Complex joins and join operations are impossible. In general, it is best to use multiple queries for this sorta thing.
  * WHERE conditions are expressed in a syntax that is incredibly verbose, but doing so improves security in some cases and will allow for more possible backends. These generally are very easy to learn, but can be a pain to write.

# Supported Backends #
  * MySQL
  * MySQLi
  * (future) PostGreSQL

# How-To Use #
## select ##

## insert ##

## update ##

## delete ##

## createTable ##

## deleteTable ##

# A Note #
In general, I have never been happy with the DAL, and kinda regret ever writing it. FreezeMessenger v3 will use it, however I may switch over to an actively developed third party version, possibly with a few changes. In other words, the database layer will likely be jettisoned in the v4 release.