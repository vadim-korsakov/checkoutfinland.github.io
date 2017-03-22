# Public API's

**TYPE: Public**

This directory contains the checkout.fi public API documentation. This is our public facing general customer documentation relating to making payments through our public API.

Start by editing the markdown in `source/index.html.md` and compiling it into slate documents with the below code.


# Getting Set Up

* Linux or OS X — Windows may work, but is unsupported.
* Ruby, version 2.2.5 or newer
* Bundler — If Ruby is already installed, but the bundle command doesn't work, just run gem install bundler in a terminal.

```shell
# either run this to run locally
bundle install
bundle exec middleman server
```

You can now see the docs at http://localhost:4567. Whoa! That was fast!

To create release ready documentation, run: `bundle exec middleman build --clean`.

Now that Slate is all set up on your machine, you'll probably want to learn more about [editing Slate markdown](https://github.com/lord/slate/wiki/Markdown-Syntax), or [how to publish your docs](https://github.com/lord/slate/wiki/Deploying-Slate).

If you'd prefer to use Docker, instructions are available [in the wiki](https://github.com/lord/slate/wiki/Docker).
