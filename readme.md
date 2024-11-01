# RESTful Posts API Plugin

A Plugin to provide a simple RESTful API to retrieve and manipulate Post data

## Usage

There are four main operations you can use with this plugin: 

- Get
- Put
- Delete
- Post

Each operation can be accessed by visiting `http://yoururl.com/postsapi/OPERATION/POST_TYPE||ID` You may pass a Post Type or ID as the second URL parameter depending on the operation you're performing. 

Each call must have a POST body containing a variable (nonce) containing a nonce value which Wordpress will verify. There is a localized JS variable provided named `secure` which should be used. Some calls also allow other POST fields to be sent along with the call.

For calls which include Field data, the plugin will automatically determine which are Custom Fields and which are not. 

See below for specifics on each operation.

### GET

*Retrieve a list of posts based on a provided Post Type or Post ID.*

#### URL Path

`http://yoururl.com/postsapi/get/POST_TYPE`

**OR**

`http://yoururl.com/postapi/get/ID`

#### POST Body

**Required**:

- nonce (nonce value for security)

**Optional**:

- arguments (an array of get_post arguments to use in the call)

**Example POST body**:

    jQuery.post( '/postsapi/get/post', { nonce: secure, arguments : {'posts_per_page': '-1'} })
      .done(function( data ) {
        console.log(data);
    });

**Additional Notes**:

The GET function returns author information for each post. If a user is logged in and has admin capabilities, the author information is extensive. If the user is not an admin, then any private information is stripped from the returned object for security purposes. 

### PUT

*Update a post based on the provided ID and POSTed fields*

#### URL Path

`http://yoururl.com/postapi/put/ID`

#### POST Body

**Required**:

- nonce (nonce value for security)
- fields (an array of key:value pairs to determine which fields to update)

**Optional**:

- force (boolean; if set to `false`, the call will return an error if any invalid field names are passed, otherwise it will update all valid fields regardless. Default is 'false')

**Example POST body**:

    jQuery.post( '/postsapi/put/1', { nonce: secure, force: false, fields: { 'post_title': 'Updated Title', 'custom_text': 'Updated custom text' } } )
    .done(function( data ) {
      console.log( data );
    });

### DELETE

*Delete a post based on the provided ID*

#### URL Path

`http://yoururl.com/postapi/delete/ID`

#### POST Body

**Required**:

- nonce (nonce value for security)

**Optional**:

- force (boolean; if set to `false`, the post will be sent to the trash, otherwise it will skip the trash and be permanently deleted. Default is 'false')

**Example POST body**:

    jQuery.post( '/postsapi/delete/7', { nonce: secure, force: false } )
    .done(function( data ) {
      console.log( data );
    });

### POST

*Create a post of the given post type, including the provided fields*

#### URL Path

`http://yoururl.com/postapi/post/POST_TYPE`

#### POST Body

**Required**:

- nonce (nonce value for security)
- fields (an array of key:value pairs to determine which fields to include when creating the post)

**Optional**:

- none

**Example POST body**:

    jQuery.post( '/postsapi/post/post', { nonce: secure, fields: { 'post_title': 'New Title', 'custom_text': 'New custom text', 'post_status': 'publish' } } )
    .done(function( data ) {
      console.log( data );
    });

## Errors and Status Messages

Each call will return a JSON object. Within the JSON object is a key named `status`. This contains a status code and status message. You can check against this in your scripts in order to see if the call was successful or not. Any status code other than 200 is an error. 

### Status Codes

- **200**: Success
- **400**: No posts found
- **401**: Permission Denied (either failed the nonce verification or user not logged in)
- **410**: Invalid Post ID or Post Type provided
- **420**: Post ID not found
- **430**: Fields not found (status message lists fields)
- **450**: Post delete failed (Wordpress could not delete the post, see the status message for details)
- **460**: Post type does not exist
- **470**: Could not create new post; see status message for error details

## Actions

Each operation has its own unique `_before` and `_after` actions that can be hooked into. See below for a complete list.

**GET**

- `postsapi_before_get`
- `postsapi_after_get`

**PUT**

- `postsapi_before_put`
- `postsapi_after_put`

**DELETE**

- `postsapi_before_delete`
- `postsapi_after_delete`

**POST**

- `postsapi_before_post`
- `postsapi_after_post`