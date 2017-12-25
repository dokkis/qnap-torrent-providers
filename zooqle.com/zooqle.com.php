<?php
class zooqle implements ISite, ISearch {
    const SITE = "https://zooqle.com";
    private $url;

    /*
     * zooqle()
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
                    "<tr \">.*".
                    "<td class=\"text-muted3 smaller pad-l2\".*>[0-9]+.</td>.*".
                    "<a class=\" small\" href=\".*\">(?P<name>.*)</a>.*".
                    "<a title=\"Generate .torrent\" rel=\"nofollow\" href=\"(?P<link>.*)\">.*</a>.*".
                    "<div class=\"progress-bar prog-blue prog-l\".*>(?P<size>.*) (?P<unit>.*)</div>.*".
                    "<div class=\"progress prog trans90\" title=\"Seeders: (?P<seeds>.*) \| Leechers: (?P<leechers>.*)\">.*</div>.*".
                    "</tr>.*".
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
                
                $tlink->src           = "zooqle.com";
                $tlink->link          = self::SITE.$result["link"][$i];
                $tlink->name          = strip_tags($result["name"][$i]);
                $tlink->size          = ($result["size"][$i] + 0) * self::UnitSize($result["unit"][$i]);
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                // $tlink->time          = $date;
                $tlink->category      = 'Unknown';
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
            if (!$ajax->request(Array("url" => zooqle::SITE."/search?q=$keyword&pg=$page"), $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>