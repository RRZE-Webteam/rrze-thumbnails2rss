# rrze-thumbnails2rss
WordPress-Plugin: Ergänzt die RSS-Ausgabe um Thumbnailangaben in verschiedenen Größen. 

Das Plugin ergänzt den RSS-Ausgabekanal von WordPress um folgende Tags zur
Angabe des Thumbnails in verschiedenen Auflösungen.
Dies soll es Drittanwendungen (z.B. Apps, die das RSS auslesen) ermöglichen, 
das Thumbnail in verschiedenen passenden Auflösungen zu addressieren.


## Funktionen:
### In der Headerdefinition

Der RSS-Header wird ergänzt um die Angabe zur Media-Spezifikation von Yahoo auf 
die, die RSS-Erweiterung beruht. 
Vgl. auch http://www.sciencemedianetwork.org/wiki/Enclosures_in_yahoo_media,_rss,_and_atom 
   

Beispiel:
 
    <?xml version="1.0" encoding="UTF-8"?><rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:media="http://search.yahoo.com/mrss/">


### Im <item>-Bereich


#### Angabe des Enclosures (Optional)
Wenn ein Beitrag ein Thumbnail (Beitragsbild) hat, wird der neue Tag
<enclosure> verwendet um das Originalbild zu verlinken.

Beispiel:

     <enclosure url="http://DOMAIN/wp-content/uploads/sites/9/2015/04/baleia-jubarte.jpg" length="87610" width="1153" height="750" type="image/jpg" />


#### Angabe des Thumbnails
Das normale Thumbnail wird wie folgt eingefügt:

   <media:thumbnail xmlns:media="http://search.yahoo.com/mrss/" url="http://DOMAIN/wp-content/uploads/sites/9/2015/04/baleia-jubarte-150x150.jpg" width="150" height="150"  type="image/jpeg" />


#### Weitere Bildauflösungen des Thumbnails:
Standardmässig werden folgende Sizes mit angegeben, falls vorhanden: medium, large, post-thumbnail 

Hierfür wird "media:content" verwendet. 

Beispiel:

     <media:content url="http://DOMAIN/wp-content/uploads/sites/9/2015/04/baleia-jubarte-300x195.jpg" medium="image" width="300" height="195" type="image/jpeg" >
 	<media:description type="plain"><![CDATA[medium]]></media:description> 
     </media:content>
     <media:content url="http://DOMAIN/wp-content/uploads/sites/9/2015/04/baleia-jubarte-1024x666.jpg" medium="image" width="1024" height="666" type="image/jpeg" >
	 <media:description type="plain"><![CDATA[large]]></media:description> 
     </media:content>
     <media:content url="http://DOMAIN/wp-content/uploads/sites/9/2015/04/baleia-jubarte-231x150.jpg" medium="image" width="231" height="150" type="image/jpeg" >
	 <media:description type="plain"><![CDATA[post-thumbnail]]></media:description> 
     </media:content>


Optional lassen sich neben dieser fest definierten Liste auch alle Bildauflösugen
ausgeben, die im System vorhanden sind.



