<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:isbd="http://iflastandards.info/ns/isbd/elements/" xmlns:owlt="http://www.w3.org/2006/time#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:bibo="http://purl.org/ontology/bibo/" xmlns:rda="http://rdvocab.info/Elements" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">

<!--
Source: https://github.com/UB-Mannheim/malibu/bnb

Copyright (C) 2013 Universitaetsbibliothek Mannheim

Author:
   Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>

This is free software licensed under the terms of the GNU GPL, 
version 3, or (at your option) any later version.
See <http://www.gnu.org/licenses/> for more details.

Beschreibung: Diese XSL Datei wandelt die RDF-Dateien
der BNB weekly in eine einfache HTML-Darstellung, wobei
 * nur Titel, die den DDC-Stellen entsprechen, herausgefiltert werden
   (die anderen Titel werden nicht angezeigt)
 * Die Sortierung alphabetisch nach Verlagsnamen passiert.
-->


<xsl:template match="/bnb">
<html>
<head>
<title>Recherche-Resultat (BNB)</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<style type="text/css">
    body { font-family:  Arial, Verdana, sans-serif; }
</style>
</head>
<body>

	<hr/><hr/>
	<xsl:for-each select="query">
		Quelle: <xsl:value-of select="source" /> ; 
		Abfrage: <xsl:value-of select="querystring" /> ; 
		Anzahl Suchergebnisse: <b><xsl:value-of select="nresults" /></b> <br/>
	</xsl:for-each>
	<hr/><hr/>

        <xsl:for-each select="rdf:Description">
            <xsl:sort select="dcterms:publisher/rdf:Description/rdfs:label" />
		<b><xsl:value-of select="dcterms:title"/></b><br/>

		<xsl:for-each select="dcterms:creator">
			<xsl:value-of select="rdf:Description/rdfs:label"/> 
		</xsl:for-each>
                [ <xsl:for-each select="dcterms:contributor">
                        <xsl:value-of select="rdf:Description/rdfs:label"/> 
                </xsl:for-each> ]<br/>

		<xsl:if test="isbd:P1008">
			<i><xsl:value-of select="isbd:P1008"/></i><br/>
		</xsl:if>

		<xsl:value-of select="isbd:P1016/rdf:Description/rdfs:label" /> :  <xsl:value-of select="dcterms:publisher/rdf:Description/rdfs:label" />, <xsl:value-of select="dcterms:issued" /> <br/>

		<xsl:for-each select="dcterms:type">
			<xsl:value-of select="rdf:Description/rdfs:label"/> ; 
                </xsl:for-each>
		<xsl:value-of select="dcterms:language/rdf:Description/rdfs:label" /> ; 
		<xsl:value-of select="dcterms:extent/rdf:Description/rdfs:label" /><br/>

		<!-- TOC  <xsl:value-of select="dcterms:tableOfContents" /><br/> -->

		<xsl:for-each select="dcterms:subject">
			<xsl:value-of select="rdf:Description/rdfs:label" /> ;
		</xsl:for-each><br/>
                
                <a target="blank">
                    <xsl:attribute name="href">http://dewey.info/class/<xsl:value-of select="dcterms:subject/rdf:Description/skos:notation" />/</xsl:attribute>
                    <xsl:value-of select="dcterms:subject/rdf:Description/skos:notation" />
                </a><br/>

		<xsl:if test="dcterms:identifier[starts-with(.,'GBB')]">
                        <xsl:value-of select="dcterms:identifier[starts-with(.,'GBB')]" /> 
                </xsl:if>
		; <span>ISBN <xsl:value-of select="bibo:isbn13" /></span> ; <span><xsl:value-of select="bibo:isbn10" /> </span>;  <xsl:value-of select="rda:termsOfAvailability" /><br/>

		<xsl:if test="dcterms:isPartOf">
			( <xsl:value-of select="dcterms:isPartOf/rdf:Description/rdfs:label" /> ) <br/>
		</xsl:if>

                <xsl:for-each select="dcterms:description">
                        <i><xsl:value-of select="." /></i><br/>
                </xsl:for-each>


		<hr/>
	</xsl:for-each>
</body>
</html>
</xsl:template>


</xsl:stylesheet>
