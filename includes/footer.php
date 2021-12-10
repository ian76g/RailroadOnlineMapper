<footer class="footer">
    <p class="footer__visitors">
        <?php

        while(!@mkdir('lock')){
        }
        file_put_contents('counter', $counter = file_get_contents('counter') + 1);
        rmdir('lock');
        echo $counter;
        ?> visitors
    </p>

    <div class="footer__small">
        <p>Created by ian76g#6577 on <a target="_blank" href="https://discord.com/channels/681431894071443457/681607137931165741">discord</a></p>
        <p>Do you love RailroadsOnlineMapper!? Help to cover hosting costs by donating to paypal@pordi.de - thx ❤️ </p>
    </div>
    <div class="footer__small" style="align-content: center">
            <?php for($i=0; $i<25; $i++){
                echo getMarqueeTrain();
            }
            ?>
    </div>

</footer>
