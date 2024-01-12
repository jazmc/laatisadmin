<div class="modal fade" id="muokkausmodal" tabindex="-1" aria-hidden="true">
    <div class="modal-lg modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalotsikko">Muokkaa tilaisuutta <span class="badge badge-secondary">ID:
                    </span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Sulje"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <input id="mod-tilid" type="hidden" value="" />
            <div class="modal-body">
                <div class="row">
                    <label class="col-sm-2 col-form-label" for="mod-otsikko">Otsikko</label>
                    <div class="col input-group">
                        <input type="text" id="mod-otsikko" name="mod-otsikko" class="form-control"
                            placeholder="(ei pakollinen)" aria-label="otsikko" aria-describedby="otsikkosel">
                    </div>
                    <label class="col-sm-3 col-form-label" for="mod-maxos">Osallistujia (max)</label>
                    <div class="col-sm-2 input-group">
                        <input type="number" id="mod-maxos" name="mod-maxos" class="form-control" aria-label="max-os"
                            value="<?php echo $oletus_osallistujamaara; ?>" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-2">
                    </div>
                    <div class="col input-group">
                        <small id="otsikkosel" class="form-text text-muted"> Jos jätät otsikon tyhjäksi, tilaisuus
                            otsikoidaan päivämäärän perusteella esimerkiksi "Elokuun tilaisuus". Syötä otsikko vain, jos
                            haluat sen olevan jotain muuta. </small>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="mod-pvm">Päivämäärät</label>
                    <div class="col input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-day" data-toggle="tooltip"
                                    data-placement="top" title="Tilaisuuden päivämäärä"></i></span>
                        </div>
                        <input type="date" class="form-control" id="mod-pvm" required />
                    </div>
                    <div class="col-sm-3 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-hourglass-start" data-toggle="tooltip"
                                    data-placement="top" title="Ilmoittautuminen alkaa"></i></span>
                        </div>
                        <input type="date" class="form-control" id="mod-ilmo" required />
                    </div>
                    <div class="col-sm-3 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" data-toggle="tooltip" data-placement="top"
                                title="Viimeinen ilmoittautumispäivä">VIP</span>
                        </div>
                        <input type="date" class="form-control" id="mod-vip" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-2 input-group justify-content-between align-items-center pr-0">
                        <label for="mod-pvm" class="mb-0">Tulokset </label>
                        <button id="mod-pyyhitulokset" type="button" class="btn btn-sm btn-outline-warning"
                            onclick="$(this).closest('.row').find('input').val('');" data-toggle="tooltip"
                            data-placement="top" title="Tyhjennä kaikki tulosrivin kentät"><i
                                class="fas fa-eraser mr-2"></i><i class="fas fa-arrow-right"></i></button>
                    </div>
                    <div class="col input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><a id="mod-tuloslinkki" target="new"><i class="fas fa-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Tulos-pdf:n URL-osoite"></i></a></span>
                        </div>
                        <input type="text" class="form-control" id="mod-tulokset" placeholder="(ei vielä tuloksia)" />
                    </div>
                    <div class="col-sm-3 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-check" data-toggle="tooltip"
                                    data-placement="top" title="Tulokset valmistuneet (pvm)"></i></span>
                        </div>
                        <input type="date" class="form-control" id="mod-valmis" disabled />
                    </div>
                    <div class="col-sm-2 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-list-ol" data-toggle="tooltip"
                                    data-placement="top" data-html="true" title="Osallistujien lkm"></i></span>
                        </div>
                        <input type="text" class="form-control" id="mod-osallistujia" disabled />
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="mod-textarea">Pisteet / palkinnot</label>
                    <div class="col input-group">
                        <textarea class="form-control" id="mod-textarea" rows="2"
                            placeholder="Pisteitä ja palkintoja ei vielä löydy tietokannasta. Copypastaa osallistujakohtaiset tulokset tähän alla olevassa muodossa."
                            disabled></textarea>
                        <small id="textareasel" class="form-text text-muted"><code>VH;PISTEET;PALKINTO;KOMMENTTI</code>
                            tai vaihtoehtoisesti
                            <code>VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET</code><br>Syötä tähän Google
                            Sheetsistä muotoonlaitetut osallistujarivit, niin järjestelmä parsii tietojen joukosta
                            pisteet ja palkinnot. Ei ole väliä, kummassa muodossa laitat pisteet. Jos Sheetsissä on
                            arvosteltu hevosia jotka eivät ilmoittautuneet tilaisuuteen tietokannan kautta, lisääthän ne
                            ensin <a href="#lisaapuuttuvia"
                                onclick="$('#muokkausmodal').modal('hide'); location.href = '#lisaapuuttuvia';">Lisää
                                puuttuvia osallistujia</a>-lomakkeen avulla!</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="mr-auto"><button type="button" class="btn btn-outline-danger"
                        onclick="poistaTilaisuus()">Poista tilaisuus</button>
                    <button id="mod-nollaa" type="button" class="btn btn-outline-warning" onclick="nollaaPisteet()"
                        data-toggle="tooltip" data-placement="top" title="Nollaa tämän tilaisuuden pisteet ja palkinnot"
                        disabled><i class="fas fa-trophy mr-1"></i><i class="fas fa-eraser"></i></button>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Sulje ikkuna</button>
                <button id="tallennamuutokset" type="button" class="btn btn-primary"
                    onclick="tallennaTilaisuus()">Tallenna muutokset</button>
            </div>
        </div>
    </div>
</div>
<div id="toaster" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;">
</div>