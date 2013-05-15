<?php

// It may take a whils to crawl a site ...
//set_time_limit(10000);

// Inculde the phpcrawl-mainclass
include("libs/PHPCrawler.class.php");

// Extend the class and override the handleDocumentInfo()-method
class MyCrawler extends PHPCrawler
{
  protected $sitemap_output_file;
  protected $dictionary = array();
  protected $url = 'http:\/\/www.primerates.com';

  public function setSitemapOutputFile($file)
  {
      $this->sitemap_output_file = $file;

      file_put_contents($this->sitemap_output_file,
                      "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\r\n",
                      FILE_APPEND);
  }

  public function handleDocumentInfo($DocInfo)
  {
        if(!in_array($DocInfo->url, $this->dictionary) && $DocInfo->http_status_code == 200 && !strpos($DocInfo->url, '(')){
            $this->dictionary[] = $DocInfo->url;

            if(preg_match('/^'.$this->url.'\/$/',$DocInfo->url)) {
                $priority   = "1.00";
                $changefreq = "daily";
            } else if(preg_match('/^'.$this->url.'\/*([a-z_-]*)+\/$/',$DocInfo->url)) {
                $priority   = "0.85";
                $changefreq = "daily";
            } else if(preg_match('/^'.$this->url.'\/calculators\/*([a-z_-]*)+\//',$DocInfo->url)) {
                $priority   = "0.85";
                $changefreq = "daily";
            }else if(strpos($DocInfo->url, '/archive/')) {
                $priority   = "0.50";
                $changefreq = "weekly";
            } else {
                $priority   = "0.69";
                $changefreq = "daily";
            }

            file_put_contents($this->sitemap_output_file,
                              "<url>".
                              "<loc>".$DocInfo->url."</loc>".
                              "<changefreq>".$changefreq."</changefreq>".
                              "<priority>".$priority."</priority>".
                              "</url>\r\n",
                              FILE_APPEND);
        }

        flush();
  }

  public function closeFile()
  {
        file_put_contents($this->sitemap_output_file, '</urlset>', FILE_APPEND);

        // Create file sitemap index
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n".
                        "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n".
                           "<sitemap>\r\n".
                              "<loc>http://www.primerates.com/sitemap_primerates.xml</loc>\r\n".
                        "<lastmod>".date(DATE_W3C, strtotime('-1 day'))."</lastmod>\r\n".
                           "</sitemap>\r\n".
                           "<sitemap>\r\n".
                              "<loc>http://www.primerates.com/listRatesInSiteMap.xml</loc>\r\n".
                              "<lastmod>".date(DATE_W3C, strtotime('-1 day'))."</lastmod>\r\n".
                           "</sitemap>\r\n".
                        "</sitemapindex>\r\n";
        file_put_contents('sitemap.xml', $content, FILE_APPEND);
  }
}

// Now, create a instance of your class, define the behaviour
// of the crawler (see class-reference for more options and details)
// and start the crawling-process.

$crawler = new MyCrawler();
// File name sitemap page primerates but not rates
$crawler->setSitemapOutputFile("sitemap_primerates.xml");
// URL to crawl
$crawler->setURL("www.primerates.com");

// Only receive content of files with content-type "text/html"
$crawler->addContentTypeReceiveRule("#text/html#");
// Ignore links to pictures, dont even request pictures
$crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png|js|css|ico)$# i");
$crawler->addURLFollowRule("/^http:\/\/www.primerates.com/");
// Ignore links with '?' after
$crawler->addNonFollowMatch("/\?/");
// Set multiprocessing
$crawler->goMultiProcessed(32);
// Thats enough, now here we go
$crawler->go();
$crawler->closeFile();
