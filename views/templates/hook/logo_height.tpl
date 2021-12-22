{*
 * 2011-2021 Blockonomics
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@blockonomics.co so we can send you a copy immediately.
 *
 * @author    Blockonomics Admin <admin@blockonomics.co>
 * @copyright 2011-2021 Blockonomics
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Blockonomics
 *}

<script>
    let container = document.querySelector("input[data-module-name=blockonomics]").parentElement.parentElement.querySelector("label")

    {foreach $blockonomicsEnabledLogos as $logo}
        img = document.createElement("img")
        img.src = "{$logo|escape:'htmlall':'UTF-8'}"
        img.style.height = "{$blockonomicsLogoHeight|intval}px"
        img.style.paddingRight = "5px"
        container.appendChild(img)
    {/foreach}
</script>
