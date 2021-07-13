    <div class="zettel_wrapper<?php if ($namespace != ""){echo " with_banner";} ?>">
        <?php
            if ($access){
            if ($namespace != ""){
                echo "<div class='external_banner'>$namespace</div>";
            }

            $orgile = new orgile();
            echo $orgile->orgileThis($content);
        ?>
        <?php
            require_once("citations.php");
            print_citations($content);
        ?>
        <?php
            require_once(__DIR__."/../config/db_connect.php");
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
            $sql = "SELECT origin_name, origin_user FROM connections WHERE target_name='$filename' AND target_user='$zetteluser'";
            $result = $mysqli->query($sql);

            if ($result->num_rows > 0) {
                echo "<div class='incoming'>";
                echo $l["Links to this zettel"].":";
                echo "<ul>";
                while($row = $result->fetch_assoc()) {
                    $origin_name = $row['origin_name'];
                    $origin_user = $row['origin_user'];

                    $res = $mysqli->query("SELECT title FROM zettel WHERE `name`='$origin_name' AND `user`='$origin_user'");
                    if ($res) {
                        $row = $res->fetch_row();
                        $origin_title =  $row[0];
                    }

                    $link_name = ($origin_user==$username)?$origin_name:$origin_user.":".$origin_name;
                    echo "<li><a href='" . $_SERVER['PHP_SELF'] . "?link=$link_name'>$origin_title</a></li>";
                }
                echo "</ul></div>";
            }
            }else{
                echo $l["Access denied"];
            }
        ?>
    </div>
    <script>
        function copyclip(content){
            navigator.clipboard.writeText("[ztl:" + content + "]");
        }

        var h1 = document.getElementsByTagName("H1")[0];
        h1.setAttribute("onclick", "copyclip('<?php echo $filename;?>')");

        var h2s = document.getElementsByTagName("H2");
        for (let i=0; i<h2s.length; i++){
            var h2 = h2s[i];
            h2.setAttribute("onclick", "copyclip('<?php echo $filename;?>#" + h2.id + "')");
        };
    </script>
    <div class="buttonbox box">
        <?php
            $filepath_bits = explode("/", $_SERVER["SCRIPT_NAME"]);
            if (end($filepath_bits ) == "edit.php"){
                echo "<a href='view.php?link=$filename' name='toview' class='button'>".$l["View"]."</a>";
            } else {
                if ($namespace == "" && !array_key_exists($username, $external_paths)){
                    echo "<a href='edit.php?link=$filename' class='button'>".$l["Edit"]."</a>";
                }
            }
        ?>
        <a class='button' onclick="toggleForm()"><?php echo $l["Menu"];?></a>
        <?php
            include("src/menu.php");
        ?>
    </div>
