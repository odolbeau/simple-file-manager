<?php

$file = $_REQUEST['file'] ?? '.';

$tmpDir = dirname($_SERVER['SCRIPT_FILENAME']);
$absolutePath = get_absolute_path($tmpDir . '/' . $file);

if($absolutePath === false) {
    err(404,'File or Directory Not Found');
}

if(substr($absolutePath, 0, strlen($tmpDir)) !== $tmpDir) {
    err(403,"Forbidden");
}

$headers = getallheaders();
if (isset($headers['X-Requested-With']) && 'XMLHttpRequest' == $headers['X-Requested-With']) {
    if (!is_dir($file)) {
        err(412,"Not a Directory");
    }

    $result = [];
    $files = array_diff(scandir($file), ['.','..']);
    foreach($files as $entry) {
        // Do not display this file if it's in the current directory
        if ($entry === basename(__FILE__)) {
            continue;
        }

        $absolutePath = $file . '/' . $entry;
        $stat = stat($absolutePath);
        $result[] = [
            'mtime' => $stat['mtime'],
            'size' => $stat['size'],
            'name' => basename($absolutePath),
            'path' => preg_replace('@^\./@', '', $absolutePath),
            'is_dir' => is_dir($absolutePath),
        ];
    }

    echo json_encode(['success' => true, 'results' => $result]);
    exit;
}

// from: http://php.net/manual/en/function.realpath.php#84012
function get_absolute_path($path) {
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $absolutes = [];
    foreach ($parts as $part) {
        if ('.' == $part) {
            continue;
        }

        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }

    return implode(DIRECTORY_SEPARATOR, $absolutes);
}

function err($code,$msg) {
    http_response_code($code);
    echo json_encode(['error' => ['code'=>intval($code), 'msg' => $msg]]);
    exit;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">

        <style>
            body {font-family: "lucida grande","Segoe UI",Arial, sans-serif; font-size: 14px;width:1024;padding:1em;margin:0;}
            th {font-weight: normal; color: #1F75CC; background-color: #F0F9FF; padding:.5em 1em .5em .2em; text-align: left;cursor:pointer;user-select: none;}
            th .indicator {margin-left: 6px }
            thead {border-top: 1px solid #82CFFA; border-bottom: 1px solid #96C4EA;border-left: 1px solid #E7F2FB; border-right: 1px solid #E7F2FB; }
            #top {height:30px;}
            label { display:block; font-size:11px; color:#555;}
            footer {font-size:11px; color:#bbbbc5; padding:4em 0 0;text-align: left;}
            footer a, footer a:visited {color:#bbbbc5;}
            #breadcrumb { font-size:15px; color:#aaa;display:inline-block;float:left;}
            a, a:visited { color:#00c; text-decoration: none}
            a:hover {text-decoration: underline}
            table {border-collapse: collapse;width:100%;}
            thead {max-width: 1024px}
            td { padding:.2em 1em .2em .2em; border-bottom:1px solid #def;height:30px; font-size:12px;white-space: nowrap;}
            td.first {font-size:14px;white-space: normal;}
            td.empty { color:#777; font-style: italic; text-align: center;padding:3em 0;}
            .is_dir .size {color:transparent;font-size:0;}
            .is_dir .size:before {content: "--"; font-size:14px;color:#333;}
            .name {
                background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABAklEQVRIie2UMW6DMBSG/4cYkJClIhauwMgx8CnSC9EjJKcwd2HGYmAwEoMREtClEJxYakmcoWq/yX623veebZmWZcFKWZbXyTHeOeeXfWDN69/uzPP8x1mVUmiaBlLKsxACAC6cc2OPd7zYK1EUYRgGZFkG3/fPAE5fIjcCAJimCXEcGxKnAiICERkSIcQmeVoQhiHatoWUEkopJEkCAB/r+t0lHyVN023c9z201qiq6s2ZYA9jDIwx1HW9xZ4+Ihta69cK9vwLvsX6ivYf4FGIyJj/rg5uqwccd2Ar7OUdOL/kPyKY5/mhZJ53/2asgiAIHhLYMARd16EoCozj6EzwCYrrX5dC9FQIAAAAAElFTkSuQmCC) no-repeat scroll 0px 12px;
                padding:15px 0 10px 40px;
            }
            .is_dir .name {
                background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADdgAAA3YBfdWCzAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAI0SURBVFiF7Vctb1RRED1nZu5977VQVBEQBKZ1GCDBEwy+ISgCBsMPwOH4CUXgsKQOAxq5CaKChEBqShNK222327f79n0MgpRQ2qC2twKOGjE352TO3Jl76e44S8iZsgOww+Dhi/V3nePOsQRFv679/qsnV96ehgAeWvBged3vXi+OJewMW/Q+T8YCLr18fPnNqQq4fS0/MWlQdviwVqNpp9Mvs7l8Wn50aRH4zQIAqOruxANZAG4thKmQA8D7j5OFw/iIgLXvo6mR/B36K+LNp71vVd1cTMR8BFmwTesc88/uLQ5FKO4+k4aarbuPnq98mbdo2q70hmU0VREkEeCOtqrbMprmFqM1psoYAsg0U9EBtB0YozUWzWpVZQgBxMm3YPoCiLpxRrPaYrBKRSUL5qn2AgFU0koMVlkMOo6G2SIymQCAGE/AGHRsWbCRKc8VmaBN4wBIwkZkFmxkWZDSFCwyommZSABgCmZBSsuiHahA8kA2iZYzSapAsmgHlgfdVyGLTFg3iZqQhAqZB923GGUgQhYRVElmAUXIGGVgedQ9AJJnAkqyClCEkkfdM1Pt13VHdxDpnof0jgxB+mYqO5PaCSDRIAbgDgdpKjtmwm13irsnq4ATdKeYcNvUZAt0dg5NVwEQFKrJlpn45lwh/LpbWdela4K5QsXEN61tytWr81l5YSY/n4wdQH84qjd2J6vEz+W0BOAGgLlE/AMAPQCv6e4gmWYC/QF3d/7zf8P/An4AWL/T1+B2nyIAAAAASUVORK5CYII=) no-repeat scroll 0px 10px;
                padding:15px 0 10px 40px;
            }
        </style>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script>
            (function($){
                $.fn.tablesorter = function() {
                    var $table = this;
                    this.find('th').click(function() {
                        var idx = $(this).index();
                        var direction = $(this).hasClass('sort_asc');
                        $table.tablesortby(idx,direction);
                    });
                    return this;
                };
                $.fn.tablesortby = function(idx,direction) {
                    var $rows = this.find('tbody tr');
                    function elementToVal(a) {
                        var $a_elem = $(a).find('td:nth-child('+(idx+1)+')');
                        var a_val = $a_elem.attr('data-sort') || $a_elem.text();
                        return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
                    }
                    $rows.sort(function(a,b){
                        var a_val = elementToVal(a), b_val = elementToVal(b);
                        return (a_val > b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
                    })
                    this.find('th').removeClass('sort_asc sort_desc');
                    $(this).find('thead th:nth-child('+(idx+1)+')').addClass(direction ? 'sort_desc' : 'sort_asc');
                    for(var i =0;i<$rows.length;i++) {
                        this.append($rows[i]);
                    }
                    this.settablesortmarkers();

                    return this;
                }
                $.fn.retablesort = function() {
                    var $e = this.find('thead th.sort_asc, thead th.sort_desc');
                    if($e.length) {
                        this.tablesortby($e.index(), $e.hasClass('sort_desc') );
                    }

                    return this;
                }
                $.fn.settablesortmarkers = function() {
                    this.find('thead th span.indicator').remove();
                    this.find('thead th.sort_asc').append('<span class="indicator">&darr;<span>');
                    this.find('thead th.sort_desc').append('<span class="indicator">&uarr;<span>');

                    return this;
                }
            })(jQuery);
            $(function(){
                var $tbody = $('#list');
                $(window).bind('hashchange',list).trigger('hashchange');
                $('#table').tablesorter();

                function list() {
                    var hashval = window.location.hash.substr(1);
                    var params = {}
                    if ('' !== hashval) {
                        params = {'file': hashval}
                    }
                    $.get('?', params, function(data) {
                        $tbody.empty();
                        $('#breadcrumb').empty().html(renderBreadcrumbs(hashval));
                        if(data.success) {
                            $.each(data.results,function(k,v){
                                $tbody.append(renderFileRow(v));
                            });
                            !data.results.length && $tbody.append('<tr><td class="empty" colspan=5>This folder is empty</td></tr>')
                        } else {
                            console.warn(data.error.msg);
                        }
                        $('#table').retablesort();
                    },'json');
                }
                function renderFileRow(data) {
                    var $link = $('<a class="name" />')
                        .attr('href', data.is_dir ? '#' + data.path : './'+data.path)
                        .text(data.name);
                    var $html = $('<tr />')
                        .addClass(data.is_dir ? 'is_dir' : '')
                        .append( $('<td class="first" />').append($link) )
                        .append( $('<td/>').attr('data-sort',data.is_dir ? -1 : data.size)
                        .html($('<span class="size" />').text(formatFileSize(data.size))) )
                        .append( $('<td/>').attr('data-sort',data.mtime).text(formatTimestamp(data.mtime)) )
                        return $html;
                }
                function renderBreadcrumbs(path) {
                    var base = "",
                        $html = $('<div/>').append( $('<a href=#>Home</a></div>') );
                    $.each(path.split('/'),function(k,v){
                        if(v) {
                            $html.append( $('<span/>').text(' ▸ ') )
                                .append( $('<a/>').attr('href','#'+base+v).text(v) );
                            base += v + '/';
                        }
                    });
                    return $html;
                }
                function formatTimestamp(unix_timestamp) {
                    var m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    var d = new Date(unix_timestamp*1000);
                    return [m[d.getMonth()],' ',d.getDate(),', ',d.getFullYear()," ",
                        (d.getHours() % 12 || 12),":",(d.getMinutes() < 10 ? '0' : '')+d.getMinutes(),
                        " ",d.getHours() >= 12 ? 'PM' : 'AM'].join('');
                }
                function formatFileSize(bytes) {
                    var s = ['bytes', 'KB','MB','GB','TB','PB','EB'];
                    for(var pos = 0;bytes >= 1000; pos++,bytes /= 1024);
                    var d = Math.round(bytes*10);
                    return pos ? [parseInt(d/10),".",d%10," ",s[pos]].join('') : bytes + ' bytes';
                }
            })
        </script>
    </head>
    <body>
        <div id="top">
            <div id="breadcrumb">&nbsp;</div>
        </div>

        <table id="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Modified</th>
                </tr>
            </thead>
            <tbody id="list">
            </tbody>
        </table>
    </body>
</html>
