# TYPO3 Extension: Social Grabber

Fetches posts from Facebook Pages, Twitter and Instagram accounts and RSS feeds to display them on your website.

Tested with TYPO3 7.6 and 8.7.

## How to set up:

This extension has to be installed via composer to load its dependencies.

````
{
  "require": {
    "smichaelsen/social-grabber": "^2.3.0",
  }
}
````

### Set up the Instagram Grabber

You need to set up an Instagram Application to run the Instagram Grabber:

* Head to the [Instagram developer's site](https://www.instagram.com/developer/)
* Click "Register Your Application" and then "Register a New Client"
* Fill in the form. Most of the fields in the Details tab are mandatory but it doesn't really matter what values you provide. Keep the fields in the Security tab as they are.
* You should see the Client ID and Client Secret which you will need in a moment.

Your Instagram Client runs in sandbox mode and __that is fine__! You don't need to pass the review process for our use case.

Configure the Instagram Application in TYPO3:

* Open the configuraion of this extension in the extension manager
* In the Instagram tab provide the Client ID and Client Secret from your Instagram Application
* In the Extension Manager click the Update Script for this extension 🔄 and from there click "Get Access Token".
* You have to log in (or be logged in) as the Instagram account that owns the application or as an account that is listed in the App as "sandbox user".
* Grant the requested access.

Create a channel and grab posts:

* Create a new channel record of the type "Instagram".
* Place the username of the desired channel in the "URL" field (just the username, not the complete URL)
* Run the scheduler task to fetch posts and optionally the one to update posts (updates the like and comment counts) 

## Semantic versioning and updates

Starting with version 2.0.0 this package uses [semantic versioning](http://semver.org/).
