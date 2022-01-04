## Description
JWT-authentication bundle for Symfony 6.

## Installation

###1. Install the bundle using composer:
```bash
$ composer require rkwadriga/jwt-bundle
```

###2. Enable the bundle in config/bundles.php:
```php
<?php

return [
    ...
    Rkwadriga\JwtBundle\RkwadrigaJwtBundle::class => ['all' => true],
];
```

###3. Create user provider and add jwt-authenticators in config/packages/security.yaml:
```yaml
security:
    ...
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    ...
    firewalls:
        ...
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticators:
                - rkwadriga.jwt.jwt_authenticator
                - rkwadriga.jwt.refresh_authenticator
                - rkwadriga.jwt.login_authenticator
   ...
```

## Configuration

### 1. Create your secret key and write it in .env:
```yaml
...
###< rkwadriga/jws-bundle ###
SECRET_KEY=890Uytsde56serKsdf098yJt540Iuyrse56Pok8O89dsWer45Ty
###> rkwadriga/jws-bundle ###
```
* Do not copy the secret-key from here. It's an important part of the authorization security. Create your own secret key, it can be any random string of any length.

### 2. Create file config/packages/rkwadriga_jwt.yaml and set your configuration
```yaml
rkwadriga_jwt:
    # The user provider that will be used to log-in the user
    provider: app_user_provider
    
    # Log-in url. By defaul it`s "POST /api/token"
    # but you can create your own url
    login_url: rkwadriga_jwt_auth_login

    # Refresh url. Url for refreshing expired token.
    # By default it`s "PUT /api/token"
    refresh_url: rkwadriga_jwt_refresh_token

    # The user identifier that you use in your user provider to identify
    # the user (usually its "email" or "username")
    user_identifier: email

    # The name of parameter that will be used in log-in request to identify the user
    login_pram: email

    # The name of parameter that will be used in log-in request to provide the password
    password_param: password

    # The secret key from .env file
    secret_key: '%env(string::SECRET_KEY)%'

    # Encoding algorithm for creating a token signature.
    # Possible values: "SHA256" and "SHA512"
    encoding_algorithm: SHA256

    # The number of hash iterations of the signature.
    # The larger the number, the slower it will work,
    # but the more difficult it is to crack the signature
    encoding_hashing_count: 3

    # Access token lifetime in seconds (one hour by default)
    access_token_life_time: 3600

    # Refresh token lifetime in seconds (6 months by default)
    refresh_token_life_time: 15552000

    # The location of access token in requests.
    # Possible values: "header", "uri" and "body"
    access_token_location: header

    # The name of access token param in request
    access_token_param_name: Authorization

    # The location of refresh token in requests.
    # Possible values: "header", "uri" and "body"
    refresh_token_location: body

    # The name of refresh token param in request
    refresh_token_param_name: refresh_token

    # Access token type. Possible values - "Bearer" and "Simple"
    token_type: Bearer

    # If you don`t want hold the refresh-tokens in your DB, set it to FALSE
    refresh_tokens_in_db: true

    # Refresh token table name. But the real table will have name "<base_name>_256"
    # or "<base_name>_512" depending on the hashing algorithm you chose
    refresh_tokens_table: refresh_token

    # The maximum count of tokens that each user can have.
    # (Actual only if the "refresh_tokens_in_db" option is enabled)
    refresh_tokens_limit: 3

    # If user tries to get the new token when the tokens
    # limit is exceeded the oldest token will be deleted
    # (if option is TRUE) or an exception will be thrown (if option is FALSE)
    rewrite_on_limit_exceeded: true
```
* All these parameters are optional, but we strongly recommend you to create your own user provider and set it`s name to "provider" option.