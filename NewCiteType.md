To add a new Cite type, modify the following parts of the code:

  1. Add the type and its parameters to the English object in proveit.descriptions.
  1. typeNameMappings in CiteReference.  If the new type is really just a redirect to a pre-existing type, add a mapping to the real type (e.g. paper->journal).  If this is the case, you're done.  Otherwise, add a self-mapping, as well as [any redirects](https://toolserver.org/~dispenser/cgi-bin/rdcheck.py) to the new type.
  1. Add an entry in requiredParams, also in CiteReference.  You can find the required parameters on the Template page (e.g. http://en.wikipedia.org/wiki/Template:Cite_episode).  These parameters will be required for the reference to be valid.
  1. Add an entry in defaultParams, in CiteReference.  This is subjective.  However, many Template pages have a "most commonly used" example, and you can use the fields from this.
  1. Add a image to the static directory and adjust iconMapping in CiteReference.
  1. Add it to the array returned from CiteReference.getTypes.
  1. Add the parameters to the array in CiteReference.getSortIndex, so the parameters will sort more meaningfully.