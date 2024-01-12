<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
    crossorigin="anonymous"></script>
<script>
    <?php if ($_SERVER['REQUEST_METHOD'] != 'POST') { ?>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
            handleBadgeColors();

            if ($(".sospisteet").length > 0) {

                venaa(2000).then(() => {
                    createToast('warning', 'Tilaisuuksia ilman pisteitä/palkintoja', 'Valmiiden tilaisuuksien joukossa on ainakin yksi tilaisuus, jolla on tulos-PDF mutta ei osallistujakohtaisia pisteitä tai palkintoja. Voit syöttää pisteet ja palkinnot tälle tilaisuudelle "Lähetä arkistotulokset" -lomakkeen avulla.');
                    $("h2[data-target='#arkistoc']").click();
                });

            }
        })
    <?php } ?>

    var uusituomari = {};
    var muokattavatuomari = {};

    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }

    function isVRL(string) {
        var reg = /^(\d){5}$/;
        if (string == '00000') {
            return false;
        }
        return reg.test(string);
    }
    // https://stackoverflow.com/a/5717133
    function validURL(str) {
        var pattern = new RegExp('^(https?:\\/\\/)' + // protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
            '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
            '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
        return !!pattern.test(str);
    }

    function venaa(aika) {
        return new Promise(resolve => setTimeout(resolve, aika));
    }

    function handleBadgeColors() {
        $(".osiosiirto").each(function () {
            $(this).not(":first-child").find(".siirra-prev").addClass("badge-secondary").attr("role", "button");
            $(this).not(":last-child").find(".siirra-next").addClass("badge-secondary").attr("role", "button");
            $(".osiosiirto:first-child").find(".siirra-prev").removeClass("badge-secondary").removeAttr("role");
            $(".osiosiirto:last-child").find(".siirra-next").removeClass("badge-secondary").removeAttr("role");
        });
    }

    $(document).on('click', '.siirra-prev.badge-secondary', function () {
        $(this).closest(".osiosiirto").insertBefore($(this).closest(".osiosiirto").prev('.osiosiirto'));
        handleBadgeColors();
    });

    $(document).on('click', '.siirra-next.badge-secondary', function () {
        $(this).closest(".osiosiirto").insertAfter($(this).closest(".osiosiirto").next('.osiosiirto'));
        handleBadgeColors();
    });

    $(document).on('click', '[data-toggle="collapse"]', function (evt) {
        evt.stopImmediatePropagation();
        $(this).find('i').toggleClass('fa-caret-down fa-caret-up');
    });

    var nth = 1;

    function createToast(type = 'secondary', otsikko = 'Ilmoitus', teksti = "", mitasitten = false) {
        var options = {
            autohide: false
        };
        if (mitasitten == 'hide') {
            options = {
                autohide: true,
                delay: 5000
            };
        }
        var toastcode = "<div class=\"toast fade\" id=\"toast" + nth + "\" role=\"alert\" aria-live=\"assertive\" aria-atomic=\"true\"><div class=\"toast-header bg-" + type + "-subtle\"><strong class=\"mr-auto text-" + type + "\">" + otsikko + "</strong><button type=\"button\" class=\"ml-2 mb-1 close\" data-dismiss=\"toast\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button></div><div class=\"toast-body\">" + teksti + "</div></div>";
        $("#toaster").append(toastcode);
        venaa(50).then(() => {
            $('#toast' + nth).addClass('slided').toast(options).toast('show');
            if (mitasitten == 'reload') {
                venaa(2500).then(() => {
                    location.reload();
                });
            }
            nth++;
        });
    }

    function lahetaKeikkatuomari() {
        if ($("#keikkaVRL").val() == "" || $("#email").val() == "") {
            alert("Tarvitsemme VRL-tunnuksesi ja sähköpostisi keikkatuomarointiohjeita varten.");
            return;
        }
        if (isEmail($("#email").val()) != true) {
            alert("Anna toimiva sähköpostiosoite.");
            $("#email").val("");
            return;
        }
        if ($(".custom-control-input:checked").length < 1) {
            alert("Valitse vähintään 1 osa-alue, jonka tuomarointeja voit tehdä.");
            return;
        }
        if ($.isNumeric($("#keikkatilaisuus").val()) === false) {
            alert("Valitse tilaisuus, jossa haluat toimia keikkatuomarina.");
            return;
        }
        $('#keikkatuomariform').submit();
    }

    function poistaKeikkatuomari(riviid) {
        $.ajax({
            'url': 'poistakeikkatuomari.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                riviid: riviid
            },
            'success': function (vastaus) {
                console.log(vastaus);
                if (vastaus.poistettu == true) {
                    createToast('success', 'Poisto onnistui', 'Keikkatuomari poistettiin onnistuneesti.', 'reload');
                } else {
                    createToast('danger', 'Poisto epäonnistui', 'Ota yhteys ylläpitoon.');
                }
            },
            'error': function (vastaus) {
                createToast('danger', 'Poisto epäonnistui', 'Ota yhteys ylläpitoon.');
                console.log(vastaus);
                console.log(vrl);
            }
        });
    }

    function tarkistaTuomarius() {
        $("#hallintanapit, #paahevoset, #varahevoset").empty();
        var vrl = $("#VRL").val();
        var tilid = $("#tilaisuusid").val();
        if (isVRL(vrl)) {
            $.ajax({
                'url': 'admin/onkotuomari.php',
                'type': 'GET',
                'dataType': 'JSON',
                'data': {
                    VRL: vrl,
                    tilid: tilid
                },
                'success': function (vastaus) {
                    console.log(vastaus);
                    if (vastaus.tuomari == true) {
                        createToast('info', 'Hei, tuomari!', 'Sinulla on tuomarioikeudet tähän tilaisuuteen.', 'hide');
                        // on
                        luoIlmolootat(vrl, true);
                    }
                    if (vastaus.tuomari == false) {
                        // ei oo tuomari
                        luoIlmolootat(vrl, false);
                    }
                },
                'error': function (vastaus) {
                    createToast('warning', 'Tuomaritietojen nouto epäonnistui', 'Kokeile uudelleen. Jos ongelma toistuu, ota yhteys ylläpitoon.');
                    console.log(vastaus);
                    console.log(vrl);
                }
            });
        } else {
            alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!");
            $("#VRL").val("");
        }
    }

    function haePalkinnotpisteet(tilid) {
        $.ajax({
            'url': 'crudjotain.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                method: 'select',
                keys: [ 'max(Palkinto) as maxPalk', 'max(Pisteet) as maxPist' ],
                table: 'Osallistuminen',
                idname: 'Til_ID',
                id: tilid
            },
            'success': function (vastaus) {
                if (!vastaus.haettu) {
                    console.log(vastaus);
                    createToast('danger', 'Virhe', 'Tilaisuuden palkinto/pistetietojen haku epäonnistui.');
                }
                if (vastaus.data[ 0 ].maxPalk != null || vastaus.data[ 0 ].maxPist) {
                    // on jo tulokset lähetetty
                    createToast('info', 'Palkinnot/pisteet tietokannassa', 'Tilaisuudella on jo osallistujakohtaiset palkinnot ja pisteet tietokannassa. Voit ylikirjoittaa tai nollata ne muokkausikkunasta.', 'hide');
                    $("#mod-textarea").attr("placeholder", "Tilaisuudella on jo osallistujakohtaiset palkinnot ja pisteet tietokannassa. Voit lähettää ne uudelleen, jos haluat ylikirjoittaa aiemmat.");
                    $("#mod-nollaa").prop("disabled", false);
                } else {
                    $("#mod-textarea").attr("placeholder", "Pisteitä ja palkintoja ei vielä löydy tietokannasta. Copypastaa osallistujakohtaiset tulokset tähän alla olevassa muodossa.");
                    $("#mod-nollaa").prop("disabled", true);
                }
            },
            'error': function (vastaus) {
                console.log(vastaus);
                createToast('danger', 'Virhe', 'Tilaisuuden palkinto/pistetietojen haku epäonnistui.');
            }
        });
    }

    function haeTilaisuus(tilid) {
        $.ajax({
            'url': 'crudjotain.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                method: 'select',
                table: 'Tilaisuus',
                idname: 'Til_ID',
                id: tilid
            },
            'success': function (vastaus) {
                if (!vastaus.haettu) {
                    createToast('danger', 'Virhe', 'Tilaisuuden haku epäonnistui.');
                }
                $("#mod-tilid").val(vastaus.data[ 0 ].Til_ID);
                $("#mod-otsikko").val(vastaus.data[ 0 ].Otsikko);
                $("#modalotsikko").find(".badge").html("ID: " + vastaus.data[ 0 ].Til_ID);
                $("#mod-maxos").val(vastaus.data[ 0 ].Maxos);
                $("#mod-pvm").val(vastaus.data[ 0 ].Pvm);
                $("#mod-ilmo").val(vastaus.data[ 0 ].IlmoAlku);
                $("#mod-vip").val(vastaus.data[ 0 ].IlmoLoppu);
                $("#mod-osallistujia").val(vastaus.data[ 0 ].Osallistujia);
                $("#mod-tulokset").val(vastaus.data[ 0 ].Tulokset);
                $("#mod-textarea").val("");
                $("#mod-valmis").val(vastaus.data[ 0 ].Valmis);
                haePalkinnotpisteet(tilid);
                if (vastaus.data[ 0 ].Tulokset != null) {
                    $("#mod-osallistujia, #mod-valmis, #mod-textarea").prop("disabled", false);
                    $("#mod-tuloslinkki").attr("href", vastaus.data[ 0 ].Tulokset);
                } else {
                    $("#mod-tuloslinkki").removeAttr("href");
                    $("#mod-osallistujia, #mod-valmis, #mod-textarea").prop("disabled", true);
                }
            },
            'error': function (vastaus) {
                createToast('danger', 'Tietojen nouto epäonnistui', 'Ota yhteys ylläpitoon.');
                console.log(vastaus);
                $("#mod-tilid").val("");
                $("#mod-otsikko").val("");
                $("#modalotsikko").find(".badge").html("ID: --");
                $("#mod-maxos").val("");
                $("#mod-pvm").val("");
                $("#mod-ilmo").val("");
                $("#mod-vip").val("");
                $("#mod-osallistujia").val("").prop("disabled", true);
                $("#mod-tulokset").val("");
                $("#mod-textarea").val("").prop("disabled", true);
                $("#mod-tuloslinkki").removeAttr("href");
                $("#mod-valmis").val("").prop("disabled", true);
            }
        });
    }

    function tallennaTilaisuus() {

        var tyhjat = $('#mod-tulokset, #mod-valmis, #mod-osallistujia').filter((i, el) => el.value.trim() === '').length;

        if (tyhjat > 0 && tyhjat < 3) {
            alert("Kaikki kolme tulokset-rivillä olevaa kenttää (pdf-linkki, valmistumispäivämäärä, osallistujamäärä) tulee täyttää, jos tilaisuuden tulokset ovat valmiit.\nJos tuloksia ei vielä ole, TYHJENNÄ kaikki tuloskentät.");
            return;
        }

        var tilid = $("#mod-tilid").val();
        var otsikko = $("#mod-otsikko").val();
        var pvm = $("#mod-pvm").val();
        var maxos = $("#mod-maxos").val();
        var ilmoalku = $("#mod-ilmo").val();
        var ilmoloppu = $("#mod-vip").val();
        var tulokset = $("#mod-tulokset").val();
        var palkinnotpisteet = $("#mod-textarea").val();
        var osallistujia = $("#mod-osallistujia").val();
        var valmis = $("#mod-valmis").val();
        if (palkinnotpisteet != "") {
            lahetaPisteet(palkinnotpisteet, tilid);
        }
        $.ajax({
            'url': 'crudjotain.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                method: 'update',
                table: 'Tilaisuus',
                keys: [ 'Otsikko', 'Pvm', 'Maxos', 'IlmoAlku', 'IlmoLoppu', 'Tulokset', 'Osallistujia', 'Valmis' ],
                keyvals: {
                    'Otsikko': otsikko,
                    'Pvm': pvm,
                    'Maxos': maxos,
                    'IlmoAlku': ilmoalku,
                    'IlmoLoppu': ilmoloppu,
                    'Tulokset': tulokset,
                    'Osallistujia': osallistujia,
                    'Valmis': valmis
                },
                idname: 'Til_ID',
                id: tilid
            },
            'success': function (vastaus) {
                createToast('success', 'Tilaisuuden muokkaus onnistui', 'Toiminto suoritettu onnistuneesti! Päivitetään sivu, hetki...', 'reload');
                if (!vastaus.muokattu) {
                    console.log("Tilaisuuden muokkaus epäonnistui")
                    createToast('danger', 'Tietojen tallennus epäonnistui', 'Ota yhteys ylläpitoon.');
                }
            },
            'error': function (vastaus) {
                createToast('danger', 'Tietojen tallennus epäonnistui', 'Ota yhteys ylläpitoon.');
                console.log(vastaus);
            }
        });
    }

    function lahetaPisteet(palkinnotpisteet, tilid, pdf = false) {
        $.ajax({
            'url': 'parsipisteet.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                palkinnotpisteet: palkinnotpisteet,
                tilid: tilid
            },
            'success': function (vastaus) {
                if (!vastaus.error && vastaus.epaonnistuneet.length < 1) {
                    createToast('success', 'Tietojen tallennus onnistui', 'Palkinnot ja pisteet tallennettiin onnistuneesti tietokantaan', false);
                    console.log("Tilaisuuden muokkaus epäonnistui");
                } else {
                    createToast('danger', 'Tietojen tallennus epäonnistui', 'Palkintojen ja pisteiden lisäys ei onnistunut ' + vastaus.epaonnistuneet.length + ' hevosen osalta. Ota yhteys ylläpitoon.', false);
                }
                if (pdf) {
                    $('#pdfform').submit();
                }
            },
            'error': function (vastaus) {
                createToast('danger', 'Tietojen tallennus epäonnistui', 'Ota yhteys ylläpitoon.');
                console.log(vastaus);
            }
        });
    }

    function lataaPdf() {
        if ($("#pdfpisteet").val() != "") {
            lahetaPisteet($("#pdfpisteet").val(), $("#tilaisuusi").val(), true);
        } else {
            $('#pdfform').submit();
        }

    }

    function haeTuomari() {
        var vrl = $("#muokattavatuomari").val();
        var tilid = $("#tilaisuusid").val();
        if (isVRL(vrl)) {
            $.ajax({
                'url': '<?php echo $domain; ?>admin/haetuomari.php',
                'type': 'GET',
                'dataType': 'JSON',
                'data': {
                    muokattavatuomari: vrl
                },
                'success': function (vastaus) {
                    if (vastaus.tuomari) {
                        $("#paivitatuomari").prop("disabled", true);
                        $("#poistatuomari").prop("disabled", false);
                        $("#mt-nimimerkki").val(vastaus.tuomari.Nimimerkki);
                        $("#mt-email").val(vastaus.tuomari.Sahkoposti);
                        $("#taukojaksot").html("");
                        $("#taukoalku").val("");
                        $("#taukoloppu").val("").prop('disabled', false);;
                        $("#toistaiseksi").removeClass("active").text('☐ Toistaiseksi');
                        for (var i = 0; i < vastaus.alueidt.length; i++) {
                            var alueid = vastaus.alueidt[ i ];
                            $("[name='alue[" + alueid + "]']").val(vastaus.alueet[ alueid ]);
                        }
                        for (var j = 0; j < vastaus.tauot.length; j++) {
                            var riviid = vastaus.tauot[ j ].Rivi_ID;
                            console.log(riviid);
                            $("#taukojaksot").append("<div>" + vastaus.tauot[ j ].Alku + "&ndash;" + (vastaus.tauot[ j ].Loppu != null ? vastaus.tauot[ j ].Loppu : "toistaiseksi") + " <i" + (vastaus.tauot[ j ].Loppu != null || ((vastaus.tauot[ j ].Loppu == null && Date.parse(vastaus.tauot[ j ].Alku) > Date.parse(new Date()))) ? " style=\"display:none;\"" : "") + " class=\"fas fa-play text-success ml-2\" onclick=\"simppeliCrud('update', 'TuomareidenTauot', 'Loppu', '<?php echo date('Y-m-d'); ?>', 'Rivi_ID', " + riviid + ")\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Katkaise tauko / aktivoi tuomari\"></i>" + " <i class=\"ml-2 fas fa-trash-alt text-danger\" data-toggle=\"tooltip\" onclick=\"simppeliCrud('delete', 'TuomareidenTauot', null, null, 'Rivi_ID', " + riviid + ")\" data-placement=\"top\" title=\"Poista taukojakso\"></i></div>");
                        }
                        $('[data-toggle="tooltip"]').tooltip()
                    }
                    if (vastaus.tuomari == false) {
                        createToast('danger', 'Virhe', 'Tuomarin tietojen nouto epäonnistui. Ota yhteys ylläpitoon.');
                    }
                },
                'error': function (vastaus) {
                    alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                    console.log(vastaus);
                    console.log(vrl);
                }
            });
        } else {
            alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!");
        }
    }

    $(document).on('change', '#muokattavatuomari', function (e) {
        haeTuomari();
    })

    function keraaMuokattavanData() {
        $("#paivitatuomari").prop("disabled", false);
        var vrl = $("#muokattavatuomari").val();
        var nick = $("#mt-nimimerkki").val();
        var email = $("#mt-email").val();
        var alueet = {};
        var alueidt = [];
        var uusitauko = {
            'alku': ($("#taukoalku").val() != "" ? $("#taukoalku").val() : null),
            'loppu': ($("#taukoloppu").val() != "" ? $("#taukoloppu").val() : null)
        };
        $("[name^='alue']").each(function () {
            var alueid = $(this).attr("name").match(/\[(.*)\]/)[ 1 ];
            var tuomarius = ($(this).val() != "" ? $(this).val() : null);
            if (tuomarius != null) {
                alueidt.push(alueid);
                alueet[ alueid ] = tuomarius;
            }
        });
        muokattavatuomari = {
            vrl,
            nick,
            email,
            alueidt,
            alueet,
            uusitauko
        };
        console.log({
            'data': {
                vrl,
                nick,
                email,
                alueidt,
                alueet,
                uusitauko
            }
        });
    }

    function keraaUudenData() {
        $("#lisaatuomari").prop("disabled", false);
        var vrl = $("#ut-VRL").val();
        var nick = $("#ut-nimimerkki").val();
        var email = $("#ut-email").val();
        var alueet = {};
        var alueidt = [];
        $("[name^='uusialue']").each(function () {
            var alueid = $(this).attr("name").match(/\[(.*)\]/)[ 1 ];
            var tuomarius = ($(this).val() != "" ? $(this).val() : null);
            if (tuomarius != null) {
                alueidt.push(alueid);
                alueet[ alueid ] = tuomarius;
            }
        });
        uusituomari = {
            vrl,
            nick,
            email,
            alueidt,
            alueet
        };
        console.log(uusituomari);
    }

    $(document).on('click', '#toistaiseksi', function (e) {
        keraaMuokattavanData();
    });

    $(document).on('change', '#muokattavatuomari, #mt-nimimerkki, #mt-email, #taukoalku, #taukoloppu, [name^="alue"]', function (e) {
        keraaMuokattavanData();
    });

    $(document).on('change', '#muokattavaosio', function (e) {
        $("#paivitaosio").prop("disabled", false);
        $("#poistaosio").prop("disabled", false);
        var otsikko = $("#muokattavaosio").find("option:selected").html();
        $("#mt-otsikko").val(otsikko);
    });

    $(document).on('change', '#ut-VRL, #ut-nimimerkki, #ut-email, [name^="uusialue"]', function (e) {
        keraaUudenData();
    });

    $(document).on('change', '#ut-osio', function (e) {
        if ($(this).val() != "") {
            $("#lisaaosio").prop('disabled', false);
        } else {
            $("#lisaaosio").prop('disabled', true);
        }
    });

    $(document).on('change', '#til-pvm', function (e) {
        if ($(this).val() != "") {
            var ilmoalku = $(this).val().slice(0, -2) + "01";
            var ilmoloppu = $(this).val().slice(0, -2) + "10";
            $("#til-ilmo").val(ilmoalku);
            $("#til-vip").val(ilmoloppu);
        }
    });

    $(document).on('change', '#til-pvm, #til-vip, #til-ilmo, #til-maxos', function (e) {
        var tyhjat = $('#til-pvm, #til-vip, #til-ilmo, #til-maxos').filter((i, el) => el.value.trim() === '').length;
        if (tyhjat < 1) {
            $("#alustatilaisuus").prop('disabled', false);
        } else {
            $("#alustatilaisuus").prop('disabled', true);
        }
    });

    $(document).on('change', '#mod-tulokset', function (e) {
        if ($(this).val() != "") {
            if (validURL($(this).val()) === false) {
                $("#mod-valmis, #mod-osallistujia, #mod-textarea").prop('disabled', true);
                $(this).val("");
                alert("Tulosten url-osoite on virheellinen.");
                return;
            }
            var now = new Date();

            var day = ("0" + now.getDate()).slice(-2);
            var month = ("0" + (now.getMonth() + 1)).slice(-2);

            var today = now.getFullYear() + "-" + (month) + "-" + (day);

            $('#mod-valmis').val(today);
            $("#mod-osallistujia").focus().click();

            $("#mod-valmis, #mod-osallistujia, #mod-textarea").prop('disabled', false);
        } else {
            $("#mod-valmis, #mod-osallistujia, #mod-textarea").prop('disabled', true);
        }
    });

    function simppeliCrud(method, table, key, val, idname, id, callback = 'reload') {
        $.ajax({
            'url': 'crudjotain.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                method: method,
                table: table,
                keys: [ key ],
                keyvals: {
                    [ key ]: val
                },
                idname: idname,
                id: id
            },
            'success': function (vastaus) {
                if (callback == 'silent') {
                    console.log(vastaus);
                    return true;
                }
                if (method == 'update' && !vastaus.muokattu) {
                    createToast('danger', 'Muokkaus epäonnistui', 'Ota yhteys ylläpitoon.');
                }
                if (method == 'delete' && !vastaus.poistettu) {
                    createToast('danger', 'Poisto epäonnistui', 'Ota yhteys ylläpitoon.');
                }
                if (method == 'insert' && !vastaus.lisatty) {
                    createToast('danger', 'Lisäys epäonnistui', 'Ota yhteys ylläpitoon.');
                }
                createToast('success', 'Onnistui', 'Toiminto suoritettu onnistuneesti! ' + (callback === 'reload' ? 'Päivitetään sivu, hetki...' : ''), callback);
            },
            'error': function (vastaus) {
                alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                console.log(vastaus);
            }
        });
    }

    function jarjestaOsiot() {
        for (var n = 1; n <= $(".osiosiirto").length; n++) {
            var alueid = $(".osiosiirto").eq(n - 1).find(".osiojarkka").val();
            simppeliCrud('update', 'Alue', 'Jarjestys', n, 'Alue_ID', alueid, 'silent');
            console.log("Alue " + alueid + ": " + n);
        }
        venaa(2500).then(() => {
            location.reload();
        });
    }

    function poistaTuomari() {
        if (confirm('Haluatko varmasti poistaa tämän tuomarin? (VRL-' + muokattavatuomari.vrl + ')\nHuomaathan, että toimintoa ei voi peruuttaa.\nJärjestelmä poistaa ensin tuomarin vastuualueet, sitten tuomarin tauot ja lopuksi koko tuomarin.')) {
            simppeliCrud('delete', 'AlueidenTuomarit', null, null, 'VRL', muokattavatuomari.vrl, false);
            simppeliCrud('delete', 'TuomareidenTauot', null, null, 'VRL', muokattavatuomari.vrl, false);
            venaa(500).then(() => {
                simppeliCrud('delete', 'Tuomari', null, null, 'VRL', muokattavatuomari.vrl);
            });
        }
    }

    function poistaOsio() {
        if (confirm('Haluatko varmasti poistaa tämän osion?\nHuomaathan, että toimintoa ei voi peruuttaa.\nJärjestelmä erottaa ensin osion tuomarit kyseiseltä vastuualueelta, ja poistaa sitten koko osion.')) {
            var muokattavaosio = $("#muokattavaosio").val();
            simppeliCrud('delete', 'AlueidenTuomarit', null, null, 'Alue_ID', muokattavaosio, false);
            venaa(500).then(() => {
                simppeliCrud('delete', 'Alue', null, null, 'Alue_ID', muokattavaosio);
            });
        }
    }

    function poistaTilaisuus() {
        var tilid = $("#mod-tilid").val();
        if (confirm('Haluatko varmasti poistaa tämän tilaisuuden? (ID: ' + tilid + ')\nHuomaathan, että toimintoa ei voi peruuttaa.\nJärjestelmä poistaa ensin tilaisuuteen liittyvät keikkatuomarit ja osallistumiset, ja sitten koko tilaisuuden.')) {
            simppeliCrud('delete', 'Keikkatuomari', null, null, 'Til_ID', tilid, false);
            simppeliCrud('delete', 'Osallistuminen', null, null, 'Til_ID', tilid, false);
            venaa(500).then(() => {
                simppeliCrud('delete', 'Tilaisuus', null, null, 'Til_ID', tilid);
            });
        }
    }

    function nollaaPisteet() {
        var tilid = $("#mod-tilid").val();
        if (confirm('Haluatko varmasti nollata tämän tilaisuuden (ID: ' + tilid + ') pisteet ja palkinnot kaikilta osallistujilta?\nHuomaathan, että toimintoa ei voi peruuttaa.')) {
            simppeliCrud('update', 'Osallistuminen', 'Pisteet', null, 'Til_ID', tilid, false);
            simppeliCrud('update', 'Osallistuminen', 'Palkinto', null, 'Til_ID', tilid, 'reload');
        }
    }

    function poistaOsallistuja(riviid) {
        if (confirm('Haluatko varmasti poistaa tämän osallistujan?\nHuomaathan, että toimintoa ei voi peruuttaa.')) {
            simppeliCrud('delete', 'Osallistuminen', null, null, 'Os_ID', riviid);
        }
    }

    function lisaaTauko(vrl) {
        if (isVRL(vrl)) {
            $.ajax({
                'url': 'crudjotain.php',
                'type': 'POST',
                'dataType': 'JSON',
                'data': {
                    method: 'insert',
                    table: 'TuomareidenTauot',
                    keys: [ 'VRL', 'Alku', 'Loppu' ],
                    keyvals: {
                        'Alku': muokattavatuomari.uusitauko.alku,
                        'Loppu': (muokattavatuomari.uusitauko.loppu != null ? muokattavatuomari.uusitauko.loppu : 'null'),
                        'VRL': muokattavatuomari.vrl
                    }
                },
                'success': function (vastaus) {
                    if (vastaus.lisatty) {
                        console.log("Tauon lisäys onnistui")
                    }
                    if (vastaus.lisatty == false) {
                        alert("Tauon lisäys epäonnistui");
                    }
                },
                'error': function (vastaus) {
                    alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                    console.log(vastaus);
                }
            });
        } else {
            alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!");
        }
    }

    function lisaaAlue(vrl, alueid, tuomarius) {
        if (isVRL(vrl)) {
            $.ajax({
                'url': 'crudjotain.php',
                'type': 'POST',
                'dataType': 'JSON',
                'data': {
                    method: 'insert',
                    table: 'AlueidenTuomarit',
                    keys: [ "Alue_ID", "VRL", "Paatoimisuus" ],
                    keyvals: {
                        "Alue_ID": alueid,
                        "VRL": vrl,
                        "Paatoimisuus": tuomarius
                    }
                },
                'success': function (vastaus) {
                    if (vastaus.lisatty == false) {
                        createToast('danger', 'Vastuualueen lisäys epäonnistui', 'Ota yhteys ylläpitoon.');
                    }
                },
                'error': function (vastaus) {
                    alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                    console.log(vastaus);
                }
            });
        } else {
            alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!")
        }
    }

    function alustaTilaisuus() {
        var otsikko = $("#til-otsikko").val();
        var pvm = $("#til-pvm").val();
        var maxos = $("#til-maxos").val();
        var ilmoalku = $("#til-ilmo").val();
        var ilmoloppu = $("#til-vip").val();
        $.ajax({
            'url': 'crudjotain.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                method: 'insert',
                table: 'Tilaisuus',
                keys: [ "Otsikko", "Pvm", "IlmoAlku", "IlmoLoppu", "Maxos" ],
                keyvals: {
                    "Otsikko": otsikko,
                    "Pvm": pvm,
                    "IlmoAlku": ilmoalku,
                    "IlmoLoppu": ilmoloppu,
                    "Maxos": maxos
                }
            },
            'success': function (vastaus) {
                if (vastaus.lisatty) {
                    createToast('success', 'Tilaisuuden alustus onnistui', 'Toiminto suoritettu onnistuneesti! Päivitetään sivu, hetki...', 'reload');
                }
                if (vastaus.lisatty == false) {
                    createToast('danger', 'Tilaisuuden alustus epäonnistui', 'Ota yhteys ylläpitoon.');
                }
            },
            'error': function (vastaus) {
                alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                console.log(vastaus);
            }
        });
    }

    function poistaAlueet(vrl) {
        if (isVRL(vrl)) {
            $.ajax({
                'url': 'crudjotain.php',
                'type': 'POST',
                'dataType': 'JSON',
                'data': {
                    method: 'delete',
                    table: 'AlueidenTuomarit',
                    idname: 'VRL',
                    id: vrl
                },
                'success': function (vastaus) {
                    if (vastaus.poistettu) {
                        console.log("alueiden nollaus onnistui")
                        for (var i = 0; i < muokattavatuomari.alueidt.length; i++) {
                            var alueid = muokattavatuomari.alueidt[ i ];
                            lisaaAlue(muokattavatuomari.vrl, alueid, muokattavatuomari.alueet[ alueid ]);
                        }
                        if (muokattavatuomari.uusitauko.alku != null) {
                            lisaaTauko(muokattavatuomari.vrl);
                        }
                    }
                    if (vastaus.poistettu == false) {
                        alert("Alueiden nollaus epäonnistui");
                    }
                },
                'error': function (vastaus) {
                    alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                    console.log(vastaus);
                }
            });
        } else {
            alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!")
        }
    }

    $(document).on("click", "#lahetajalkiilmot", function (e) {
        var tilid = $("#jalk-tilid").val();
        var jalkiilmo = $("#jalkiilmot").val();

        $.ajax({
            'url': 'jalkiilmo.php',
            'type': 'POST',
            'dataType': 'JSON',
            'data': {
                tilid: tilid,
                jalkiilmo: jalkiilmo
            },
            'success': function (vastaus) {
                console.log(vastaus);
                if (!vastaus.error && vastaus.epaonnistuneet.length < 1) {
                    createToast('success', 'Tietojen lähetys onnistui', 'Toiminto suoritettu onnistuneesti! Päivitetään sivu, hetki...', 'reload');
                } else {
                    createToast('danger', 'Tietojen lähetys epäonnistui', 'Vähintään yhden hevosen tietojen lähetyksessä oli ongelmaa. Lisätiedot ilmestyvät lomakkeen yläpuolelle. Päivitetään sivu, hetki...', 'reload');
                }
            },
            'error': function (vastaus) {
                alert("Jokin meni vikaan! Yritä uudelleen.\nJos ongelma toistuu, ota yhteys ylläpitoon.");
                console.log(vastaus);
            }
        });

    });

    $(document).on("click", "#siirrajalkiilmot", function (e) {
        var vh = $("#jalk-VH").val();
        var rotu = $("#jalk-rotu").val();
        var skp = $("#jalk-skp").val();
        var nimi = $("#jalk-nimi").val();
        var linkki = $("#jalk-linkki").val();
        var poikkeukset = $("#jalk-poikkeukset").val();
        var pisteet = $("#jalk-pisteet").val();
        var palkinto = $("#jalk-palkinto").val();

        $("#jalkiilmot").append(vh + ";" + pisteet + ";" + palkinto + ";" + rotu + "-" + skp + ";" + nimi + ";" + linkki + ";" + poikkeukset + "\n").trigger("input");
        $(".kriittinen").not("#jalk-tilid").val("");
        $("#jalk-poikkeukset").val("");
        $("#jalk-pisteet").val("");
        $("#jalk-palkinto").val("").trigger("change");
    });

    $(document).on('click', '#lisaatuomari', function (e) {
        if (uusituomari.vrl && uusituomari.nick && isEmail(uusituomari.email)) {
            if (isVRL(uusituomari.vrl)) {
                $.ajax({
                    'url': 'crudjotain.php',
                    'type': 'POST',
                    'dataType': 'JSON',
                    'data': {
                        method: 'insert',
                        table: 'Tuomari',
                        keys: [ 'VRL', 'Nimimerkki', 'Sahkoposti' ],
                        keyvals: {
                            'VRL': uusituomari.vrl,
                            'Nimimerkki': uusituomari.nick,
                            'Sahkoposti': uusituomari.email
                        }
                    },
                    'success': function (vastaus) {
                        if (vastaus.lisatty) {
                            //onnistui
                            for (var i = 0; i < uusituomari.alueidt.length; i++) {
                                var alueid = uusituomari.alueidt[ i ];
                                lisaaAlue(uusituomari.vrl, alueid, uusituomari.alueet[ alueid ])
                            }
                            createToast('success', 'Lisäys onnistui', 'Toiminto suoritettu onnistuneesti! Päivitetään sivu, hetki...', 'reload');
                        }
                        if (vastaus.lisatty == false) {
                            createToast('danger', 'Lisäys epäonnistui', 'Ota yhteys ylläpitoon.');
                        }
                    },
                    'error': function (vastaus) {
                        if (vastaus.responseJSON.info.errorInfo[ 1 ] == 1062) {
                            createToast('warning', 'Virhe: tuplalisäys', 'Kyseinen tuomari löytyi jo tuomaritietokannasta.');
                        } else {
                            createToast('danger', 'Lisäys epäonnistui', 'Ota yhteys ylläpitoon.');
                        }
                        console.log(vastaus);
                    }
                });
            } else {
                alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!")
            }
        }
    });

    $(document).on('click', '#paivitatuomari', function (e) {
        if (muokattavatuomari.vrl && muokattavatuomari.nick && isEmail(muokattavatuomari.email)) {
            if (isVRL(muokattavatuomari.vrl)) {
                $.ajax({
                    'url': 'crudjotain.php',
                    'type': 'POST',
                    'dataType': 'JSON',
                    'data': {
                        method: 'update',
                        table: 'Tuomari',
                        keys: [ 'Nimimerkki', 'Sahkoposti' ],
                        keyvals: {
                            'Nimimerkki': muokattavatuomari.nick,
                            'Sahkoposti': muokattavatuomari.email
                        },
                        idname: 'VRL',
                        id: muokattavatuomari.vrl
                    },
                    'success': function (vastaus) {
                        if (vastaus.muokattu) {
                            // perustietojen muokkaus onnistui
                            poistaAlueet(muokattavatuomari.vrl);
                            createToast('success', 'Muokkaus onnistui', 'Toiminto suoritettu onnistuneesti! Päivitetään sivu, hetki...', 'reload');
                        }
                        if (vastaus.muokattu == false) {
                            createToast('danger', 'Muokkaus epäonnistui', 'Ota yhteys ylläpitoon.');;
                        }
                    },
                    'error': function (vastaus) {
                        createToast('danger', 'Muokkaus epäonnistui', 'Ota yhteys ylläpitoon.');
                        console.log(vastaus);
                    }
                });
            } else {
                alert("VRL-tunnus on virheellinen. Syötä vain numero-osa, 5 numeroa!")
            }
        }
    });

    function luoIlmolootat(vrl, tuomari) {
        var tilid = $("#tilaisuusid").val();
        $.ajax({
            'url': 'admin/kaivaoslkm.php',
            'type': 'GET',
            'dataType': 'json',
            'data': {
                VRL: vrl,
                Tuomari: tuomari,
                Til_ID: tilid
            },
            'success': function (vastaus) {
                if (vastaus) {
                    var paahepat = vastaus[ 0 ];
                    var varahepat = vastaus[ 1 ];
                    var varadis = "";
                    if (varahepat < 1) {
                        varadis = " disabled";
                    }
                    var paadis = "";
                    if (paahepat < 1) {
                        paadis = " disabled";
                    }
                    $("#hallintanapit").append("<div><strong>Lisää ilmoittautumisrivejä painikkeista:</strong></div>")
                    $("#hallintanapit").append("<div class=\"form-group row\"><div class=\"col\"><div class=\"input-group\"><div class=\"input-group-prepend\"><button class=\"btn btn-primary\" id=\"paamiinus\" type=\"button\" onclick=\"poistaRivi(1, false)\" disabled>-</button></div><input type=\"text\" class=\"form-control\" value=\"Hevoset (max. " + paahepat + ")\" aria-describedby=\"basic-addon1\" style=\"text-align:center;\" readonly><div class=\"input-group-append\"><button class=\"btn btn-primary\" id=\"paaplus\" onclick=\"lisaaRivi(" + paahepat + ", false)\" type=\"button\"" + paadis + ">+</button></div></div></div><div class=\"col\"><div class=\"input-group\"><div class=\"input-group-prepend\"><button class=\"btn btn-warning\" id=\"varamiinus\" type=\"button\" onclick=\"poistaRivi(0, true)\" disabled>-</button></div><input type=\"text\" class=\"form-control\" value=\"Varahevoset (max. " + varahepat + ")\" aria-describedby=\"basic-addon1\" style=\"text-align:center;\" readonly><div class=\"input-group-append\"><button class=\"btn btn-warning\" id=\"varaplus\" onclick=\"lisaaRivi(" + varahepat + ", true)\" type=\"button\"" + varadis + ">+</button></div></div></div></div>");
                    $("#paahevoset").append("<strong>Hevoset</strong>");
                    $("#varahevoset").append("<strong>Varahevoset</strong>");
                }
            },
            'error': function (vastaus) {
                alert("Jokin meni vikaan! Ota yhteys ylläpitoon.");
                console.log(vastaus);
                console.log(vrl);
            }
        });
    }

    var nthrivi = 0;

    function lisaaRivi(max, vara) {
        var tilid = $("#tilaisuusid").val();
        if ($("#submitnappi").html() == "") {
            $("#submitnappi").append("<button type=\"submit\" id=\"lahnappi\" class=\"btn btn-success btn-lg btn-block\">Lähetä ilmoittautumiset</button>");
        }
        var kohde;
        var varaheppanro;
        if (vara == true) {
            varaheppanro = 1;
            kohde = $("#varahevoset");
        } else {
            varaheppanro = 0;
            kohde = $("#paahevoset");
        }
        if (kohde.children(".hepparivi").length < max) {
            kohde.append("<div class=\"hepparivi\" style=\"margin-bottom:3em;\"><div class=\"row form-group\"><div class=\"col\"><input type=\"text\" name=\"os[" + nthrivi + "][Rotu]\" class=\"form-control\" placeholder=\"rotulyh.\" required></div><div class=\"col\" style=\"flex-grow:2;\"><select name=\"os[" + nthrivi + "][Skp]\" class=\"form-control\" id=\"exampleFormControlSelect1\" required><option value=\"\" selected disabled>Valitse sukupuoli</option><option value=\"t\">t</option><option value=\"o\">o</option><option value=\"r\">r</option></select></div><div class=\"col\" style=\"flex-grow:3;\"><input type=\"text\" name=\"os[" + nthrivi + "][Nimi]\" class=\"form-control\" placeholder=\"Hevosen Nimi\" required></div><div class=\"col\" style=\"flex-grow:3;\"><input type=\"text\" name=\"os[" + nthrivi + "][Linkki]\" class=\"form-control\" placeholder=\"https://hevosenosoite.com\" required></div></div> <div class=\"row form-group\"><div class=\"col\" style=\"flex-grow:3;\"><input type=\"text\" name=\"os[" + nthrivi + "][Poikkeukset]\" class=\"form-control\" placeholder=\"Lisätiedot / poikkeukset\"></div><div class=\"col\"><input type=\"text\" name=\"os[" + nthrivi + "][VH]\" class=\"form-control\" placeholder=\"VH00-000-0000\" required></div></div><input type=\"hidden\" name=\"os[" + nthrivi + "][Varahevonen]\" value=\"" + varaheppanro + "\"/><input type=\"hidden\" name=\"os[" + nthrivi + "][Til_ID]\" value=\"" + tilid + "\"/></div>");
            if (vara == true) {
                $("#varamiinus").prop("disabled", false);
            } else {
                $("#paamiinus").prop("disabled", false);
            }
        }
        if (kohde.children(".hepparivi").length == max) {
            if (vara == true) {
                $("#varaplus").prop("disabled", true);
            } else {
                $("#paaplus").prop("disabled", true);
            }
        }
        nthrivi++;
    }

    function poistaRivi(min, vara) {
        var kohde;
        if (vara == true) {
            kohde = $("#varahevoset");
        } else {
            kohde = $("#paahevoset");
        }
        if (kohde.children(".hepparivi").length > min) {
            kohde.children(".hepparivi:last").remove();
            if (vara == true) {
                $("#varaplus").prop("disabled", false);
            } else {
                $("#paaplus").prop("disabled", false);
            }
        }
        if (kohde.children(".hepparivi").length == min) {
            if (vara == true) {
                $("#varamiinus").prop("disabled", true);
            } else {
                $("#paamiinus").prop("disabled", true);
            }
        }
    }

    $(document).on("change", "[name$='[VH]'], [id$='VH']", function () {
        var reg = /^(VH)(\d){2}\-(\d){3}\-(\d){4}$/;
        if (!reg.test($(this).val())) {
            $(this).val("");
            alert("VH-tunnuksen muoto virheellinen, tarkista!");
        }
    });

    $(document).on("change", "[name$='[Linkki]'], #jalk-linkki", function () {
        if (validURL($(this).val()) === false) {
            $(this).val("");
            alert("Hevosen URL-osoite on virheellinen, tarkista!");
        }
    });

    $(document).on("change", "[type='email']", function () {
        if (isEmail($(this).val()) === false) {
            $(this).val("");
            alert("Antamasi sähköpostiosoite on virheellinen, tarkista!");
        }
    });

    $(document).on("change", "[name$='VRL']", function () {
        if (isVRL($(this).val()) === false) {
            $(this).val("");
            alert("Antamasi VRL-tunnus on virheellinen, tarkista!");
        }
    });

    $(document).on("change", "#VRL, #tilaisuusid", function () {
        if (isVRL($("#VRL").val()) === false || $("#tilaisuusid").val() == null) {
            $("#aloita").prop("disabled", true);
        } else {
            $("#aloita").prop("disabled", false);
        }
    });

    $(document).on("change", "#keikkaVRL, #email, #keikkatilaisuus, .keikkacheck", function () {
        if ($("#keikkaVRL").val() != null && $("#email").val() != null && $("#keikkatilaisuus").val() != null && $(".keikkacheck:checked").length > 0) {
            $("#lahetakeikka").prop("disabled", false);
        } else {
            $("#lahetakeikka").prop("disabled", true);
        }
    });

    $(document).on("change", ".kriittinen, #jalk-poikkeukset, #jalk-pisteet, #jalk-palkinto", function () {
        if ($(this).hasClass("kriittinen") && $(this).val() == "") {
            $(this).addClass("is-invalid")
        } else {
            $(this).removeClass("is-invalid")
        }
        if ($('.kriittinen').filter(function () {
            return this.value.trim() == '';
        }).length == 0) {
            $("#siirrajalkiilmot").prop("disabled", false);

        } else {
            $("#siirrajalkiilmot").prop("disabled", true);
        }
    });

    $(document).on("input", "#jalkiilmot, #jalk-tilid", function () {
        if ($("#jalk-tilid").val() > 0 && $("#jalkiilmot").val() != "") {
            var reg = /^((VH)(\d){2}\-(\d){3}\-(\d){4})\;(\d*[\,\.]*\d*)\;([^\;]*)\;([^\;]*)\-([tor])\;([^\;]*)\;([^\;]*)\;([^\;]*)\n*$/;
            var rivit = $("#jalkiilmot").val().split("\n");
            var vialliset = 0;
            rivit.forEach(element => {
                if (element != "" && !reg.test(element)) {
                    $("#jalkiilmot").addClass("is-invalid");
                    $("#lahetajalkiilmot").prop("disabled", true);
                    vialliset += 1;
                }
            });
            if (vialliset < 1) {
                $("#jalkiilmot").removeClass("is-invalid");
                $("#lahetajalkiilmot").prop("disabled", false);
            }
        } else {
            $("#lahetajalkiilmot").prop("disabled", true);
        }

        if ($("#jalk-tilid").val() > 0 === false) {
            $("#jalk-tilid").addClass("is-invalid");
        } else {
            $("#jalk-tilid").removeClass("is-invalid");
        }
    });

    $(document).on("input", "#pdfpisteet, #mod-textarea", function () {
        if ($(this).val() != "") {
            var reg = /^((VH)(\d){2}\-(\d){3}\-(\d){4})\;(\d*[\,\.]*\d*)\;([^\;]*)\;([^\;]*)\-([tor])\;([^\;]*)\;([^\;]*)\;([^\;]*)\n*$/;
            var lyh = /^((VH)(\d){2}\-(\d){3}\-(\d){4})\;(\d*[\,\.]*\d*)\;([^\;]*)\;(.*)\n*$/;
            var rivit = $(this).val().split("\n");
            var vialliset = 0;
            rivit.forEach(element => {
                if (element != "" && !reg.test(element) && !lyh.test(element)) {
                    $(this).addClass("is-invalid");
                    $("#lahetajalkiilmot").prop("disabled", true);
                    vialliset += 1;
                }
            });
            if (vialliset < 1) {
                $(this).removeClass("is-invalid");
                if ($(this).attr("id") == "pdfpisteet") {
                    $("#lataapdf").prop("disabled", false);
                } else if ($(this).attr("id") == "mod-textarea") {
                    $("#tallennamuutokset").prop("disabled", false);
                }
            } else {
                if ($(this).attr("id") == "pdfpisteet") {
                    $("#lataapdf").prop("disabled", true);
                } else if ($(this).attr("id") == "mod-textarea") {
                    $("#tallennamuutokset").prop("disabled", true);
                }
            }
        }
    });

    $(document).on("input", "#tilaisuusi, #osi, #pdfi, #pdfpisteet", function () {
        if ($("#tilaisuusi").val() != "" && $("#pdfi").files.length > 0 && $("#osi").val() != "" && $("#pdfpisteet").val() != "") {
            $("#lataapdf").prop("disabled", false);
        } else {
            $("#lataapdf").prop("disabled", true);
        }
    });



</script>