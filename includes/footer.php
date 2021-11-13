<footer class="footer">
  <p class="footer__visitors">
    <?php file_put_contents('counter',
        $counter = file_get_contents('counter') + 1); echo $counter; ?> visitors
  </p>

  <div class="footer__small">
  <p>Created by ian76g#6577 on <a target="_blank" href="https://discord.com/channels/681431894071443457/681607137931165741">discord</a></p>
    <p>Do you love RailroadsOnlineMapper!? Help to cover hosting costs by donating to paypal@pordi.de - thx ❤️ </p>
  </div>
</footer>