# Introduction #

Add your content here.


# Details #

  * div#proveit
    * div#tabs
      * h1 - title
      * ul - tabs
      * span.notification
      * div#view-tab
        * div#view-pane
          * div.scroll
            * table#refs
              * tbody
                * tr#dummyRefs
                * tr.dark and tr.light
        * div#edit-pane
          * div.ref-name-row
            * label + input
          * div#edit-fields
            * div.preloadedrow input-row
              * label + input
          * div#edit-buttons
            * buttons
      * div#dummyCitePane
        * div.ref-name-row
          * label + input
        * div.paramlist
      * div#preloadedparamrow
        * label + input
        * button.remove
      * div#addedparamrow
        * input + input
        * button.remove
      * add-tab
        * div#add-fields
          * div#cite
            * label.paramdesc required
            * select#citemenu
          * div#citepanes
            * div#web .addpanes
              * div.ref-name-row
                * label + input
              * div.paramlist
                * div.preloadedrow input-row
          * div#citation
            * label.paramdesc required
            * select#citemenu
          * div#citationpanes
        * div#add-buttons
          * buttons
          * span.required