# About this

Markdown parser that implements (the commonmark specs)[http://spec.commonmark.org/0.24/].

This parser is strongly inspired by https://github.com/cebe/markdown. I basically rewrote it after getting a grasp of how it worked, for self-educative purposes. I don't know if the original respects the commonmark specs, though.

I intend to add three small things to the spec'd markdown: image dimension control (because that's convenient), image collages (because that's cool, and I've used it in the past), and "back-clickable" footnotes (because that's really handy).

# Installation

composer require j11e/markdown 

# Usage

No CLI tool right now, just a Parser class with a public `parse()` method.

So, `$parser = new j11e\markdown\Parser();`, then `$parser->parse()`.