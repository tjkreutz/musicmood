# Database-driven webtechnologie project proposal #

### Introduction ###

Our website has two purposes: Firstly, it is about classifying music. We want to create a crowdsourcing-project where users rate and classify music to get metadata related to songs. We then store this data in our database.
The second purpose is to show the results from our database on the website, via playlists.

### Crowdsourcing ###

Mainly, we need users to classify songs by mood. This should produce a categorization of music that relates to the mood of a user. So, a user can choose to classify music if he feels like it, but if he is more of a passive listener, he can choose one of the playlists to go with his mood.
Crowdsourcing comes in handy in more than one regard: it allows users to add new music, and rate music as spam or 'unfit' to keep the website free of junk.

### Different users ###

Users need to register before they can vote. Users can earn achievements according to the amount of successful votes they have cast. This stimulates serious commitment to the sites' community. Admins keep order in the worst scenario's. They have different rights than users, they can report spam and delete duplicates.
At first a user can only specify moods for a song, like happy, sad, excited or mad. When a user has matched enough songs to a mood it can earn points and ‘our trust’. The more points a user has, the more rights he will get. This goes according to a table we’ve made for the website.

| Moderator rank    | Amount of points earned | Spam-vote value |
|-------------------|-------------------------|-----------------|
| Untrusted/Spammer | -15                     | 0               |
| Neutral/New User  | 0                       | 0.5             |
| Trust Rank 5      | 100                     | 2               |
| Trust Rank 4      | 200                     | 4               |
| Trust Rank 3      | 400                     | 8               |
| Trust Rank 2      | 800                     | 16              |
| Trust Rank 1      | 1600                    | 32              |

The minus points are a way to register untrusted users in the system. After a lot of users have marked a song it becomes clear to the system what mood it will be. Whenever a user matches a completely different mood to a song e.g. happy when 1000 users have marked it as sad this users will get a negative point. Of course peoples’ opinions differ from time to time so a user will only be reported as untrusted after a larger number of negative points. Another way to avoid spam on the website is by creating a ‘spam button’ where users can report a song. New users don’t have much influence on a song, but higher ranked users get more influence in the decision on whether or not a song is spam.

### Database ###

The initial database includes registered users and songs who are related to each other by the users ranking the songs, or by a user uploading a song.
The user needs a unique email-address that will function as the primary key.

The song ID primary key will be the youtube extension, since these are different for each video it will prevent some issues of duplicate songs. All relation-tables give us information on user history. Therefore, all actions get a timestamp, and the rates-table gets a mood assigned. Prototype: The front page will show a demo of what the website is and explains what is does. Also a little example from a playlist will be visible. Users can register and log-in to classify songs. Other pages are playlists, upload and achievements another page will be where users can rank the songs, the evaluation page.

Bo Blankers // David de Kleer // Tim Kreutz // Tom Bouwhuis