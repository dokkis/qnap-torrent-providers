<?php
class limetorrents implements ISite, ISearch {
    const SITE = "https://www-limetorrents-cc.pbproxy.lol"; // use a proxy because limetorrents.cc go into localhost in my QNAP.
    private $url;
    
    /*
     * kickass()
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
        switch (strtoupper($unit)) {
        case "KB": return 1000;
        case "MB": return 1000000;
        case "GB": return 1000000000;
        case "TB": return 1000000000000;
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
                "`".
                    "<tr.*".
                    "<a href=\"(?P<link>http://itorrents.*)\".*</a>.*".
                    "<a.*>(?P<name>.*)</a>.*".
                    "<td class=\"tdnormal\">(?P<time>.*) - in (?P<category>.*)</a></td>.*".
                    "<td class=\"tdnormal\">(?P<size>.*) (?P<unit>.*)</td>.*".
                    "<td class=\"tdseed\">(?P<seeds>.*)</td>.*".
                    "<td class=\"tdleech\">(?P<leechers>.*)</td>.*".
                    "</tr>".
                "`siU",
                $body,
                $result
            );

            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;
                
                $tlink->src           = "limetorrents.cc";
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
            if (!$ajax->request(Array("url" => limetorrents::SITE."/search/all/$keyword/date/$page"), $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>
