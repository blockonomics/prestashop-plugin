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

<style>
    .blockonomics-hidden { 
        display: none !important; 
    }
    .blockonomics-crypto-logo-container {
        display: inline-block;
    }
    .blockonomics-crypto-logo {
        height: {$blockonomicsLogoHeight|intval}px;
        padding-left: 5px;
    }
</style>

<script id="blockonomics-hook-logo-height">
    let image = document.querySelector("img[src*='blockonomics/views/img']")
    let image_container = document.createElement("span")
    image_container.classList.add("blockonomics-crypto-logo-container")

    {foreach $blockonomicsEnabledLogos as $logo}
        img = document.createElement("img")
        img.src = "{$logo|escape:'htmlall':'UTF-8'}"
        img.classList.add("blockonomics-crypto-logo")
        image_container.appendChild(img)
    {/foreach}
    
    image.parentNode.replaceChild(image_container, image)
    document.getElementById("blockonomics-hook-logo-height").parentElement.classList.add("blockonomics-hidden")
</script>
