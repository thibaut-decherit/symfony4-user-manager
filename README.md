# Symfony 4 User Manager
My take on user management with Symfony 4.4.

Continuation of [symfony3-user-manager](https://github.com/thibaut-decherit/symfony3-user-manager).


## **Dependencies**
- Webpack Encore (SCSS and JS compilation)
- jQuery (AJAX and DOM manipulation)
- Bootstrap 4 (forms and alerts)
- [zxcvbn](https://github.com/dropbox/zxcvbn) (password strength estimation)

## **Features**

Feel free to tailor each feature to your needs.

### Internationalization
- Content is compatible with translation files
  - English translation included
  - Add your own, modify existing one

### Mailer
- Service
- Add your own methods to send other emails
- Keep in mind that emails are translated in the locale of the current user, which could differ from the locale of the recipient, especially for password reset email and emails related to user enumeration prevention. If it's a problem for you, consider storing the locale of each user in the database and translating emails with this locale instead of the default one

### Registration
- Registration form with expected validations on each field (see User entity for details)
- Form submitted with AJAX to avoid refresh, so you can freely embed it in another view (e.g. in a modal)
- Custom Symfony form errors
- Custom flash message with Bootstrap alert success on successful registration
- Activation link sent to newly registered user, leading to confirmation view with a button
  - On button click, redirect to login page with custom flash message with Bootstrap alert success

### Login with Guard component
- Login form, submitted with AJAX to avoid refresh, so you can freely embed it in another view (e.g. in a modal)
  - Username/email compatible field
- Bootstrap alert danger with message on wrong credentials
- Redirect to login page on access attempt to page requiring authentication
- Remember me
- Guard
  - Add your own logic to what happens when user logs in successfully (e.g. redirect to specific route depending on user role)
  - Add your own logic to what happens when user fails to log in (e.g. count before account timeout)

### Logout
- Logout route
- CSRF token support to prevent malicious logout by third-parties

### User profile
- Account information edit form with username field (add fields as needed)
- Form embedded in parent view through Twig `{{ render(controler()) }}` , so you can group it together with other account related forms
- Form submitted with AJAX to avoid refresh
- Custom Symfony form errors
- Bootstrap alert success with message on successful edit

### Email address change
- Email change form submitted with AJAX to avoid refresh
- Form embedded in parent view through Twig `{{ render(controler()) }}`, so you can group it together with other account related forms
- Custom Symfony form errors
- Custom flash message with Bootstrap alert success on successful submit
- Verification link and link lifetime sent to the new email address, leading to confirmation view with a button
  - On button click, redirect to login page with custom flash message with Bootstrap alert success
- Customizable delay between each change request
  - Prevents user from spamming another email address
  - If delay is not expired, shows successful submit flash message
- Customizable verification link lifetime
  - Custom flash message with Bootstrap alert danger if verification link has expired

### Password change
- Password change form submitted with AJAX to avoid refresh
- Form embedded in parent view through Twig `{{ render(controler()) }}`, so you can group it together with other account related forms
- Current password field
- Repeat new password field
- Custom Symfony form errors
- Custom flash message with Bootstrap alert success on successful change

### Password reset
- Password reset form with username/email compatible field
- Custom flash message with Bootstrap alert success on successful submit
- Customizable delay between each reset request
  - Shows previous success flash message even if delay is not expired (email is probably on the way and the user is impatient)
- Customizable reset link lifetime
  - Custom flash message with Bootstrap alert danger if reset link has expired
- Email with reset link and link lifetime sent to user
- On reset password form submit, account is considered to be activated if it wasn't already
- On reset success, redirect to login page and custom flash message with Bootstrap alert success

### Account deletion
- Request modal reachable only if user is logged-in
- Customizable deletion link lifetime
  - Custom flash message with Bootstrap alert danger if deletion link has expired
- Confirmation link and link lifetime sent to user, leading to confirmation view with two buttons
  - On delete button click, account is deleted and user is redirected to homepage with custom flash message with Bootstrap alert success
  - On cancel button click, user fields related to account deletion are cleared, invalidating the deletion link, and user is redirected to homepage

### Redirect if authenticated
- Event listener triggered on each request through `onKernelRequest()` method 
- Redirect to homepage if authenticated user attempts to access "logged-out only" routes (e.g. login, register and password reset)
- Add your own routes and modify existing list

### Password rehash on user authentication if cost change
- Event listener triggered on login through `onSecurityInteractiveLogin` method
- Rehashes password on login if algorithm or algorithm options have been modified in `config/packages/security.yaml` 
  - Works automatically with any algorithm supported by the `algorithm: auto` setting (Bcrypt, Argon2i and Argon2id)
    - User logs in with old algorithm/options
    - Password is then automatically hashed and stored according to current preferred algorithm/options
  - Without this listener, hash change would apply only to password persisted (registration) or updated (password change or reset) after the change
  - This could be an issue if your existing users don't update their password
  - A workaround would be to force your users to change password but it is bad practice for multiple reasons and you could have to deal with distrust ("Why are you asking me that? Have you been hacked? Is my data safe?")
  - This listener prevents all that by working seamlessly in the backgroup while your users log in
- Password checked through `password_needs_rehash`  method
- Argon2id implementation
- Modify listener and config files to implement algorithms not supported by the `auto` [encoder](https://symfony.com/blog/new-in-symfony-4-3-native-password-encoder). If you need to switch algorithm on an existing database, see [here](https://gist.github.com/thibaut-decherit/fb041311b6e387132a8077062acd6ded#the-following-is-optional-needed-if-you-have-passwords-hashed-with-legacy-algorithms-eg-sha-1-you-have-two-options)

### Haveibeenpwned API password validator
- Prevents your users from choosing a password compromised in known data breaches
- Password validation through Troy Hunt [haveibeenpwned.com](https://haveibeenpwned.com/) API
- Custom Symfony form error
- Consider implementing this through something less strict than a validator if you think it could deter potential users (e.g. an informative message on user profile or a password strength meter)

### Password strength meter
- Usable separately or conjointly with the back-end HIBP password validator
- Visual indicator ONLY, to help your users choose a "good" password
- Password strength is based on length, [zxcvbn](https://github.com/dropbox/zxcvbn) password strength estimator from Dropbox and a check against previously leaked passwords through Troy Hunt [haveibeenpwned.com](https://haveibeenpwned.com/) API (if available)

### Unactivated accounts removal command
- Command to delete users registered for more than `d` days if they haven't activated their account
- Removes accounts that will most probably never be used
- Modify time between registration and removal as needed
- Execute `php bin/console app:remove-unactivated-accounts-older-than d` command (e.g. through a cron job)

### User enumeration prevention
- Registration
  - If form is valid, shows success message even if email address is already registered to another account
  - If email address is already registered to another account and is:
    - verified: sends an email to the existing user, suggesting him to reset his password (we assume user is trying to create a new account because he forgot the password)
    - unverified: sends an email to the existing user with a verification link (similar logic)
- Login
  - Same error message if wrong password or if user doesn't exist
  - If email address is not yet verified a new verification email is sent
- Password reset
  - If form is valid, shows success message even if email address or username is not registered to any account
  - If email address or username is registered to an account and retry delay is expired or inexistant (first try), sends the email
- Email address change
  - If form is valid and new email address is not the same than current email address, shows success message
    - If new email address is not already registered to another account and retry delay is expired or inexistant (first try), also sends a verification email
    - if new email address is already registered to another account, only shows success message without sending a verification email (we assume this is an enumeration attempt)
  - If form is valid but new email address is the same than current email address, shows error message

**Important:** Spool emails from file should be enabled in production environment or the delay between form submission and server response could hint that an email has been sent.

### Response header setter
- Event listener triggered on each response through `onKernelResponse()` method
- Adds custom headers to the response
- Support for "static" headers specified in `config/response_header_setter/response_headers.yaml`
  - Currently includes security / privacy related headers:
    - Referrer-Policy
    - Strict-Transport-Security (remember to register the domain on [https://hstspreload.org/](https://hstspreload.org/) or preload will not work)
    - X-Content-Type-Options
    - X-Frame-Options
    - X-XSS-Protection
- Support for "dynamic" headers generated according to specific parameters (app environment, requested route...)
  - Currently includes a Content Security Policy header generator and setter:
    - Allows you to protect your users from malicious resources (e.g. malicious JavaScript code that could end up in your dependencies, like [this one](https://blog.npmjs.org/post/180565383195/details-about-the-event-stream-incident))
    - Two level policy, lax & strict, in case you want to make sure critical routes are better protected (e.g. your website consumes an API with Ajax/fetch or requires a CDN for specific features, but you want to make sure this API or CDN cannot compromise your most critical routes, like login or checkout, if they ever become compromised [themselves](https://www.troyhunt.com/the-javascript-supply-chain-paradox-sri-csp-and-trust-in-third-party-libraries/))
    - Customizable directives for each policy level through a config file in `config/response_header_setter/content_security_policy.yaml` (modify existing ones, add your own)
    - Supports `report-uri`, two modes:
      - `plain`: specify the URL of your report-uri logger endpoint
      - `match`: specify the route name, router will handle URL generation. Can only be used if your report-uri logger is part of the same application
    - Dev environment directives to generate (less secure) directives allowing Symfony Profiler to work properly. The Profiler relies on inline JS and CSS, which you are strongly advised to block in production environment to counter XSS. Current whitelists block these by default in production environment.
