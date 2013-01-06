How Business Syncronization Works
=================================

The following are the steps required to sync the business
information found in the ciniki.businesses module.  This does not
include sync information for other modules.

This module syncs the tables ciniki_business_users and ciniki_business_user_details.  

The following are the steps performed when syncronizing the module.

1. Get the remote user list.

This will retrieve the list user uuid's and last_updated date from the remote server
of any users who are attached to the business in ciniki_business_users.  It will also
get the list if deleted users from that business.

If an incremental sync, it will only return UUID's which have changed since 
the last incremental sync.

2. Check for deleted users.

Go through the list of deleted users that have been removed on 
the remote server, and make sure they have been deleted on the 
local server.



1. Get the history list
