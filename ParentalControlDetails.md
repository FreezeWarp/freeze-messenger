**DRAFT: Partially Implemented**

The parental control system is implemented for a small number of reasons. While the shear existence may almost be offensive to the most conservative of persons, the system hopes to allow all kinds of content -- especially that which may not be appropriate to younger audiences -- within the system, while obscuring such content as appropriate.

It is modeled from similar ratings from American rating agencies like the ESRB and MPAA. It hopes to take into account culture differences where reasonable - for instance, pnudity exists to signify content that may be common in some countries (such as Europe or Japan), but less so in others (such as the United States).

# Flags #
Flags indicate the type of content that is in the message, regardless of its age. They can be set by an administrator, but the following default flags are used:
  * violence - Punching, kicking, fighting, etc.
  * weapons - Weapons, firearms, knives, etc.
  * gore - Open wounds, blood, etc.
  * nudity - Visible frontal nudity, etc.
  * suggestive - Sexual themes, etc.
  * drugs - Drugs, tobacco, alcohol, etc.
  * language - Explicit language, etc.
  * gambling

# Age-Rating #
Age ratings indicate the approximate age an upload or room is appropriate for. The following default ages are used:
  * 6 - All ages. Default.
  * 10 - 10+ (ex. PG).
  * 13 - 13+ (ex. PG-13).
  * 16 - 16+ (ex. R).
  * 18 - 18+ (ex. NC-17).

# Implementation #
  * By default, the user's DOB will be used to generate age, and will restrict content to that age group.
  * Unless restricted by an administrator, users will be able to disable the system with an over-ride for a specific age, regardless of what is default.
  * Users can opt-in (and possibly opt-out) to certain flags which block items based on content.
  * Administrators may place a number of controls throughout to fine-tune the system, including:
    * Default flags for users.
    * Whether or not the age can be changed. (It will always be possible to decrease the age used.)
    * Minimum DOB for registration.

# Further Considerations #
  * Re-implement the censor according to these rules such that:
    * Censor-lists can be labelled with an age rating.
    * Rooms that disable censor-lists are required to be at least the age rating of the censor-list.
  * A minor feature request was to allow for a custom user-set censor. Add this to the WebPro interface if reasonable (it may be too complicated for normal end-users).
  * The "age" and "flag" logic are separate by design. A matrix could theoretically be used, but instead the logic will be implemented as anything above parentalAge will be bared, as will any content falling into any one of the categories will be bared. The hope is that when a room is labeled "age=13", "flags=language,drugs" you know that it is both only appropriate for people 13 and above, and only appropriate for people comfortable with those two topics.
  * Parental controls will not be enabled for rooms if a user is the owner or moderator of that room, or an admin.