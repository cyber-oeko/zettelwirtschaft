<?php // Time-stamp: <2011-11-27 Sun 16:21 classOrgile.php>

/*
  ______________________
  C L A S S  O R G I L E

  classOrgile a very rough Org-Mode (http://orgmode.org/) file to HTML parser.
  This class is part of the Orgile publishing tool but can be used as a 
  standalone class. Please see http://toshine.org.

  Version 20110418

  Copyright (c) 2011 , 'Mash (Thomas Herbert) <letters@toshine.org>
  All rights reserved.

  This project was inspired by Dean Allen's "Textile" http://textile.thresholdstate.com/.

  NOTE: If you would like to help me develop this class properly rather then this
  amateur garden shed effort; please do contact me on the above address.

  _____________
  L I C E N S E

  This file is part of Orgile.
  Orgile: an Emacs Org-mode file parser and publishing tool.

  Orgile is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  Orgile is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Orgile.  If not, see <http://www.gnu.org/licenses/>.

  _____________________
  D E S C R I P T I O N

  ClassOrgile converts "some" Org-mode markup into HTML. And yes you are right
  to question why since Org-mode has a mature HTML export already. http://orgmode.org

  require_once('classOrgile.php');
  $orgile = new orgile();
  return $orgile->orgileThis($content);


  The following org-mode markup is converted to HTML.
  Various glyphs are also replaced with their HTML entities.
  i.e. " (opening double quote) -> &#8220;

  * This is an example title.     -> <h1>This is an example title.</h1>
  ** This is an example title.    -> <h2>This is an example title.</h2>
  *** This is an example title.   -> <h3>This is an example title.</h3>
  **** This is an example title.  -> <h4>This is an example title.</h4>
  ***** This is an example title. -> <h5>This is an example title.</h5>

  This is an example of a paragraph. -> <p>This is an example of a paragraph</p>

  *example* -> <strong>example</strong>
  /example/ -> <em>example</em>
  +example+ -> <del>example</del>

  ----- -> <hr>

  #+begin_quote
  This is an example quote. -- Some Author. Some publication, 1975.
  #+end_quote

  -> <blockquote cite="Some Author. Some publication, 1975."><p>&#8220;This is an example quote.#8221;</p></blockquote><p class="citeRef">Some Author. Some publication, 1975.</p>

  #+begin_example
  This is an example.
  #+end_example

  -> <pre>This is an example.</pre>

  #+begin_src
  <?php print "hello world!" ?>
  #+end_src

  -> <pre><code><?php print "hello world!" ?></code></pre>

  [[http://www.link.com][example]] -> <a href="http://www.link.com" title="example">example</a>

  This is an example sentence with footnote.[1] -> This is an example sentence with footnote.<sup class="fnote"><a href="#fn1">1</a></sup>
  [1] This is an example footnote.              -> <p class="fnote"><sup id="fn1" class="fnote">1</sup>This is an example footnote.</p>',

*/

// ------------------------------[ CLASS ORGILE ]------------------------------
class orgile {

  // ----------[ ORGILE ]----------
  function orgileThis($text) {
    $text = $this->orgilise($text);
    $text = $this->orgilise_links($text);
    $text = $this->orgilise_links_external($text);
    $text = $this->tidy_lists($text);
    $text = $this->codeReplace($text);
    $text = $this->footnotes($text);
    $text = $this->paragraph($text);
    return $text;
  }

  // ----------[ ORGALISE CONTENT ]----------
  // replace some general Org-mode markup with HTML.
  // NOTE: careful with changing order as links may be "glyphed"

  function orgilise($text) {
    global $namespace, $self_namespace;
    $script_name = $_SERVER['PHP_SELF'];
    $namespace_prefix = ($namespace == "") ? $namespace:$namespace.":";
  

    $regex = array(
       // roam
       '/^\#\+title:{1}\s+?(.+)/i',         # #+TITLE:
       '/^\#\+roam_tags:{1}\s+?(.+)/im',     # #+ROAM_TAGS:
       '/^\#\+created:{1}\s+?(.+)/im',       # #+CREATED:
       '/^\#\+last_modified:{1}\s+?(.+)/im', # #+LAST_MODIFIED:

		   // headings
		   '/^\*{1}\s+?(.+)/m', // * example
		   '/^\*{2}\s+?(.+)/m', // ** example
		   '/^\*{3}\s+?(.+)/m', // *** example
		   '/^\*{4}\s+?(.+)/m', // **** example
		   '/^\*{5}\s+?(.+)/m', // ***** example

		   // typography
		   '/(?<!\S)\*(.+?)\*/m', // *example*
		   '/(?<!\S)\/(.+?)\//m', // /example/
		   '/(?<!\S)\+(.+?)\+/m', // +example+

       // list
       '/^\s{2}[\+\-\*]\s?(.+)/m',   // 1st level
       '/^\s{4}[\+\-\*]\s?(.+)/m',   // 2st level
       '/^\s{6}[\+\-\*]\s?(.+)/m',   // 3st level

        // numbered list
        '/^\s{2}[1-9][\)\.]\s?(.+)/m',   // 1st level
        '/^\s{4}[1-9][\)\.]\s?(.+)/m',   // 2st level 
        '/^\s{6}[1-9][\)\.]\s?(.+)/m',   // 3st level 

		   // glyphs
		   // kudos: "Textile" http://textile.thresholdstate.com/.
		   '/(\w)\'(\w)/',                   // apostrophe's
		   '/(\s)\'(\d+\w?)\b(?!\')/',       // back in '88
		   '/(\S)\'(?=\s|[[:punct:]]|<|$)/', // single closing
		   '/\'/',                           // single opening
		   '/(\S)\"(?=\s|[[:punct:]]|<|$)/', // double closing
		   '/"/',                            // double opening
		   '/\b( )?\.{3}/',                  // ellipsis
		   '/(\s\w+)--(\w+\s)/',              // em dash
		   '/\s-(?:\s|$)/',                  // en dash
		   '/(\d+)( ?)x( ?)(?=\d+)/',        // dimension sign
		   '/\b ?[([]TM[])]/i',              // trademark
		   '/\b ?[([]R[])]/i',               // registered
		   '/\b ?[([]C[])]/i',               // copyright

		   // horizontal rule
		   '/-{5}/', // ----- (<hr/>)

		   // citations
		   //'/#\+begin_quote\s([\s\S]*?)\s--\s(.*?)\s#\+end_quote/mi',
       '/#\+begin_quote/m',
       '/#\+end_quote/m',

		   // pre
		   '/#\+begin_example\s([\s\S]*?)\s#\+end_example/mi',

		   // source
		   '/#\+begin_src\s?(\S+?)\n([\s\S]*?)\s#\+end_src/mi',

		   // links
       '/\[\[ext\:'.$self_namespace.'\:(.+?)\]\[(.+?)\]\]/m', // backlink to this zettelkasten
       '/\[\[file\:(.+?).org\]\[(.+?)\]\]/m', // intern
       '/\[\[ztl\:(.+?)\]\[(.+?)\]\]/m', // intern
       '/\[\[ext\:(.+?)\]\[(.+?)\]\]/m', // other orgroam zettelkasten
       '/\[\[(.+?)\]\[(.+?)\]\]/m', // extern

		   );

    $replace = array(
         // roam
         "<h1>$1</h1>\n", // #+TITLE:
         "<div class=roam_tags>$1</div>",
         "<div class=created>$1</div>",
         "<div class=last_modified>$1</div>",


		     // headings
		     "<h2>$1</h2>\n", // * example
		     "<h3>$1</h3>\n", // ** example
		     "<h4>$1</h4>\n", // *** example
		     "<h5>$1</h5>\n", // **** example
		     "<h6>$1</h6>\n", // ***** example

		     // typography
		     "<strong>$1</strong>", // *example*
		     "<em>$1</em>",         // /example/
		     "<del>$1</del>",       // +example+

         // list
         "<ul><li>$1</li></ul>",
         "<ul><ul><li>$1</li></ul></ul>",
         "<ul><ul><ul><li>$1</li></ul></ul></ul>",

        // ordered list
        "<ol><li>$1</li></ol>",
        "<ol><ol><li>$1</li></ol></ol>",
        "<ol><ol><ol><li>$1</li></ol></ol></ol>",

		     // glyphs
		     "$1&#8217;$2",  // apostrophe's&#8220;
		     "$1&#8217;$2",  // back in '88
		     "$1&#8217;",    // single closing
		     "&#8216;",      // single opening
		     "$1&#8221;",    // double closing
		     "&#8220;",      // double opening
		     "$1&#8230;",    // ellipsis
		     "$1&#8212;$2",  // em dash
		     "&#8211;",      // en dash
		     "$1$2&#215;$3", // dimension sign
		     "&#8482;",      // trademark
		     "&#174;",       // registered
		     "&#169;",       // copyright

		     // horizontal rule
		     "<hr>", // ----- (<hr>)

		     // citations (because of the cite="$2" these fail W3M validation)
		     //'<blockquote cite="$2"><p>$1</p></blockquote><p class="citeRef">$2</p>',
         '<blockquote>',
         '</blockquote>',

		     // pre
		     '<pre>$1</pre>',

		     // source
		     '<pre><code class="$1">$2</code></pre>',

        //links
         '<a href="' . $script_name . '?link=$1" name="zettelkasten_link" class="external_zettelkasten" title="$2">$2</a>', // backlink to this zettelkasten
         '<a href="' . $script_name . '?link='.$namespace_prefix.'$1" name="zettelkasten_link" class="internal" title="$2">$2</a>', // intern
         '<a href="' . $script_name . '?link='.$namespace_prefix.'$1" name="zettelkasten_link" class="internal" title="$2">$2</a>', // intern
         '<a href="' . $script_name . '?link=$1" name="zettelkasten_link" class="external_zettelkasten" title="$2">$2</a>', // other orgroam zettelkasten

		     '<a href="$1" title="$2" class="external_internet" target="_blank">$2</a>', // extern
		     );

    return preg_replace($regex,$replace,$text);
  }


  function orgilise_links($text) {
    $script_name = $_SERVER['PHP_SELF'];
    $regex = '/\[ztl\:(.+?)\]/m';

    function callback($pattern){
      global $namespace, $script_name;
      $namespace_prefix = ($namespace == "") ? $namespace:$namespace.":";
      $linktitle = get_title_from_name($namespace, $pattern[1]);
      return '<a href="'.$script_name.'?link='.$namespace_prefix.$pattern[1].'" name="zettelkasten_link" class="internal" title="'.$linktitle.'">'.$linktitle.'</a>';
    }
    return preg_replace_callback($regex,"callback",$text);
  }

  function orgilise_links_external($text) {
    $script_name = $_SERVER['PHP_SELF'];
    $regex = '/\[ext\:(.+?)\]/m';

    function callback_ext($pattern){
      global $script_name, $self_namespace;
      $filename = explode(":", $pattern[1])[1];
      $namespace = explode(":", $pattern[1])[0];
      $namespace = $namespace == $self_namespace?"":$namespace;
      $linktitle = get_title_from_name($namespace, $filename);
      $namespace_prefix = ($namespace == "") ? $namespace:$namespace.":";
      return '<a href="'.$script_name.'?link='.$namespace_prefix.$filename.'" name="zettelkasten_link" class="external_zettelkasten" title="'.$linktitle.'">'.$linktitle.'</a>';
    }
    return preg_replace_callback($regex,"callback_ext",$text);
  }


  // Tidy up lists
  function tidy_lists($text) {
    $regex = '/\<\/[uo]l>\n?<[uo]l>/im';
    $replace = "";
    return preg_replace($regex,$replace,$text);
  }

  // ----------[ CREATE FOOTNOTES ]----------
  // footnotes follow the pattern "example[n]" for id,  "[n] " for reference.
  function footnotes($text) {
    $regex = array(
		   '/(\S)\[([1-9]|[1-9][0-9])\]/',   // example[1]
		   '/\n\[([1-9]|[1-9][0-9])\](.*)/', // [1] example
		   );

    $replace = array(
		     '$1<sup class="fnote"><a href="#fn$2">$2</a></sup>',
		     '<p class="fnote"><sup id="fn$1" class="fnote">$1</sup>$2</p>',
		     );

    return preg_replace($regex,$replace,$text);
  }

  // ----------[ CODE REPLACE ]----------
  // use \"blah" in code and it will translated back into the "
  function codeReplace($code) {
    $dirty = array('\&#8216;','\&#8217;','\&#8220;','\&#8221;');
    $clean = array("'","'",'"','"');
    $code = str_replace($dirty, $clean, $code);
    return $code;
  }

  // ----------[ PARAGRAPHS AND CLEANUP TAGS ]----------
  // create paragraphs and cleanup HTML tags.
  function paragraph($text) {
    $paragraphs = explode("\n", $text);
    $out = null;
    foreach($paragraphs as $paragraph) {
      $out .= "\n<p>".$paragraph."</p>\n";
    }

    // cleanup paragraphs
    // due to the simplicity of the above there are many incorrect nested tags
    // i.e. <h1> elements inclosed in <p> tags.

    $regex = array(
		   '/<p>(<h[1-9]{1}>.+<\/h[1-9]{1}>)<\/p>/m',         // <p><h1>example</h1></p>
		   '/<p>(<blockquote>[\s\S]+?)<\/p>/m',               // <p><blockquote>example</blockquote></p>
		   '/<p>(<blockquote cite=".+?">[\s\S]+?)<\/p>/m',    // <p><blockquote cite="example">example</blockquote></p>
		   '/<p>(<pre>[\s\S]+?<\/pre>)<\/p>/m',               // <p><pre>example</pre></p>
		   '/<p>(<p class="fnote">[\s\S]*?)\s+<\/p>/m',       // <p><p class="footnote">example</p></p>
		   '/(<\/p>)\s+<\/p>/m',                              // <p></p>
		   '/<p>(<hr>)<\/p>/',				      // <p><hr></p>
		   );

    $replace = array(
		     "$1", // <hx>example</hx>
		     "$1", // <blockquote>example</blockquote>
		     "$1", // <blockquote cite="example">example</blockquote>
		     "$1", // <pre>example</pre>
		     "$1", // <p class="footnote">example</p>
		     "$1", //
		     "$1", // <hr>
		     );

    $out = preg_replace($regex,$replace,$out);
    return $out;
  }

} // end: "class orgile {"
?>
