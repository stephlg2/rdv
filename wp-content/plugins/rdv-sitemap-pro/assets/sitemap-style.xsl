<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>Sitemap XML - Rendez-vous avec l'Asie</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <meta name="robots" content="noindex,follow"/>
                <style type="text/css">
                    * { box-sizing: border-box; }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background: #f5f5f5;
                        color: #333;
                        margin: 0;
                        padding: 20px;
                        line-height: 1.6;
                    }
                    .container {
                        max-width: 1200px;
                        margin: 0 auto;
                    }
                    .header {
                        background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
                        color: #fff;
                        padding: 30px;
                        border-radius: 12px;
                        margin-bottom: 25px;
                        box-shadow: 0 4px 15px rgba(222, 91, 9, 0.3);
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 28px;
                    }
                    .header p {
                        margin: 10px 0 0;
                        opacity: 0.9;
                    }
                    .stats {
                        display: flex;
                        gap: 20px;
                        margin-bottom: 25px;
                        flex-wrap: wrap;
                    }
                    .stat-box {
                        background: #fff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                        min-width: 150px;
                    }
                    .stat-number {
                        font-size: 32px;
                        font-weight: 700;
                        color: #de5b09;
                    }
                    .stat-label {
                        color: #666;
                        font-size: 14px;
                    }
                    .content {
                        background: #fff;
                        border-radius: 12px;
                        padding: 25px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th {
                        text-align: left;
                        padding: 15px 12px;
                        background: #f8f9fa;
                        border-bottom: 2px solid #e0e0e0;
                        font-weight: 600;
                        color: #333;
                        font-size: 13px;
                        text-transform: uppercase;
                    }
                    td {
                        padding: 12px;
                        border-bottom: 1px solid #eee;
                        font-size: 14px;
                    }
                    tr:hover td {
                        background: #fafafa;
                    }
                    a {
                        color: #0073aa;
                        text-decoration: none;
                        word-break: break-all;
                    }
                    a:hover {
                        color: #de5b09;
                        text-decoration: underline;
                    }
                    .priority {
                        display: inline-block;
                        padding: 4px 10px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        background: #e8f5e9;
                        color: #2e7d32;
                    }
                    .priority.high {
                        background: #fff3e0;
                        color: #e65100;
                    }
                    .changefreq {
                        color: #666;
                        font-size: 13px;
                    }
                    .images-count {
                        background: #e3f2fd;
                        color: #1565c0;
                        padding: 3px 8px;
                        border-radius: 12px;
                        font-size: 12px;
                    }
                    .footer {
                        text-align: center;
                        padding: 20px;
                        color: #666;
                        font-size: 13px;
                    }
                    .footer a {
                        color: #de5b09;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🗺️ Sitemap XML</h1>
                        <p>Rendez-vous avec l'Asie - Voyages sur mesure en Asie</p>
                    </div>
                    
                    <xsl:choose>
                        <!-- Sitemap Index -->
                        <xsl:when test="sitemap:sitemapindex">
                            <div class="stats">
                                <div class="stat-box">
                                    <div class="stat-number"><xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/></div>
                                    <div class="stat-label">Sitemaps</div>
                                </div>
                            </div>
                            
                            <div class="content">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Sitemap</th>
                                            <th>Dernière modification</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                                            <tr>
                                                <td>
                                                    <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                                                </td>
                                                <td>
                                                    <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                                                </td>
                                            </tr>
                                        </xsl:for-each>
                                    </tbody>
                                </table>
                            </div>
                        </xsl:when>
                        
                        <!-- URL Set -->
                        <xsl:otherwise>
                            <div class="stats">
                                <div class="stat-box">
                                    <div class="stat-number"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></div>
                                    <div class="stat-label">URLs</div>
                                </div>
                            </div>
                            
                            <div class="content">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>URL</th>
                                            <th>Priorité</th>
                                            <th>Fréquence</th>
                                            <th>Images</th>
                                            <th>Modifié</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <xsl:for-each select="sitemap:urlset/sitemap:url">
                                            <tr>
                                                <td>
                                                    <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                                                </td>
                                                <td>
                                                    <xsl:choose>
                                                        <xsl:when test="number(sitemap:priority) &gt;= 0.8">
                                                            <span class="priority high"><xsl:value-of select="sitemap:priority"/></span>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <span class="priority"><xsl:value-of select="sitemap:priority"/></span>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </td>
                                                <td class="changefreq">
                                                    <xsl:value-of select="sitemap:changefreq"/>
                                                </td>
                                                <td>
                                                    <xsl:if test="count(image:image) &gt; 0">
                                                        <span class="images-count">
                                                            <xsl:value-of select="count(image:image)"/> img
                                                        </span>
                                                    </xsl:if>
                                                </td>
                                                <td>
                                                    <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                                                </td>
                                            </tr>
                                        </xsl:for-each>
                                    </tbody>
                                </table>
                            </div>
                        </xsl:otherwise>
                    </xsl:choose>
                    
                    <div class="footer">
                        <p>
                            Généré par <a href="https://www.rdvasie.com" target="_blank">RDV Sitemap Pro</a>
                            | <a href="https://www.rdvasie.com/plan-du-site/" target="_blank">Plan du site HTML</a>
                            | <a href="https://www.rdvasie.com/llms.txt" target="_blank">llms.txt (IA)</a>
                        </p>
                    </div>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>

