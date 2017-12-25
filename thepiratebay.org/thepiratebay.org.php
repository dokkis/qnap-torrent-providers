<?php
class thepiratebay implements ISite, ISearch {
    const SITE = "https://thepiratebay.rocks"; // use a proxy because thepiratebay.org go into localhost in my QNAP.
    private $url;

    /*
     * thepiratebay()
     * @param {string} $url
     * @param {string} $username
     * @param {string} $password
     * @param {string} $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = null) {
        $this->url = $url;
    }

    /*
     * UnitSize()
     * @param {string} $unit
     * @return {number} sizeof byte
     */
    static function UnitSize($unit) {
        switch ($unit) {
        case "KiB": return 1000;
        case "MiB": return 1000000;
        case "GiB": return 1000000000;
        case "TiB": return 1000000000000;
        default: return 1;
        }
    }
    
    /*
     * Search()
     * @param {string} $keyword
     * @param {integer} $limit
     * @param {string} $category
     * @return {array} SearchLink array
     */
    public function Search($keyword, $limit, $category) {
        $page = 1;
        $keyword = urlencode($keyword);
        
        $ajax = new Ajax();
        $found = array();
        $success = function ($_, $_, $_, $body, $_) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`<tr.*".
                    "<td.*<center>.*<a.*>(?P<category>.*)</a><br>.*</center>.*</td>.*" .
                    "<td.*<div.*<a href=\"(?P<descriptionLink>.*)\".*>(?P<name>.*)</a>.*</div>.*</td>.*".
                    "<a href=\"(?P<link>magnet:.*)\".*</a>.*".
                    "<font.*>Uploaded (?P<time>.*), Size (?P<size>.*)&nbsp;(?P<unit>[a-zA-Z]*),.*</font>.*".
                    "<td.*>(?P<seeds>\d+)</td>.*".
                    "<td.*>(?P<leechers>\d+)</td>.*".
                "</tr>.*`siU",
                $body,
                $result
            );

            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;

                $tlink->src           = "thepiratebay.org";
                $tlink->link          = $result["link"][$i];
                $tlink->name          = strip_tags($result["name"][$i]);
                $tlink->size          = ($result["size"][$i] + 0) * self::UnitSize($result["unit"][$i]);
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                // $tlink->time          = $date;
                $tlink->category      = $result["category"][$i];
                $tlink->enclosure_url = $tlink->link;
                
                $found []= $tlink;
                
                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }
            
            ++$page;
        };
        
        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request(Array("url" => thepiratebay::SITE."/search/$keyword/$page/99/0"), $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>