class Mapper {
    constructor(json) {
        this.svgNS = "http://www.w3.org/2000/svg";
        this.svgTag = null;
        this.shapes = [];
        this.json = json;
        this.drawMaxSlope = true;

        this.imageWidth = 8000;
        this.minX = -200000;
        this.maxX = 200000;
        this.minY = -200000;
        this.maxY = 200000;
        this.x = this.maxX - this.minX;
        this.y = this.maxY - this.minY;
        this.max = Math.max(this.x, this.y);
        this.scale = (this.imageWidth * 100 / this.max);
        this.switchRadius = (80 / 2.2107077) * this.scale;
        this.engineRadius = 6 * this.scale;
        this.turnTableRadius = (10 / 2.2107077) * this.scale;
        this.imx = this.x / 100 * this.scale;
        this.imy = this.y / 100 * this.scale;
        this.maxSlope = 0;
        this.allLabels = [[0, 0]];
        this.initialTreesDown = 1750;
    }

    drawSVG(htmlElement) {
        this.svgTag = document.getElementById(htmlElement);
        this.getReplantableTrees();
        this.getTracksAndBeds();
        this.getSwitches();
        this.getTurntables();
        this.getRollingStock();
        this.getIndustries();
        this.getWaterTowers();

        this.populatePlayerTable();

        for (const shape of this.shapes) {
            this.svgTag.appendChild(shape);
        }
    }

    populatePlayerTable() {
        if (!('Players' in this.json)) {
            return null
        }

        const playerTable = document.getElementById("playerTable");
        const editPlayersTable = document.getElementById("editPlayersTable");

        for (let index = 0; index < this.json['Players'].length; index++) {
            const player = this.json['Players'][index];

            // First populate the Info menu
            const playerInfoRow = document.createElement("tr");

            const nameValue = document.createElement("td");
            const nameTextNode = document.createTextNode(player['Name']);
            nameValue.appendChild(nameTextNode);
            playerInfoRow.appendChild(nameValue);

            const xpValue = document.createElement("td");
            const xpTextNode = document.createTextNode(player['Xp']);
            xpValue.appendChild(xpTextNode);
            playerInfoRow.appendChild(xpValue);

            const moneyValue = document.createElement("td");
            const moneyTextNode = document.createTextNode(player['Money']);
            moneyValue.appendChild(moneyTextNode);
            playerInfoRow.appendChild(moneyValue);

            playerTable.appendChild(playerInfoRow);

            // Then populate the Edit menu
            const playerEditInfoRow = document.createElement("tr");

            const playerEditValue = document.createElement("td");
            const playerEditTextNode = document.createTextNode(player['Name']);
            playerEditValue.appendChild(playerEditTextNode);
            playerEditInfoRow.appendChild(playerEditValue);

            const playerEditXpValue = document.createElement("td");
            const playerEditXpInput = document.createElement("input");
            playerEditXpInput.size = 5;
            playerEditXpInput.maxLength = 15;
            playerEditXpInput.name = "xp_" + index;
            playerEditXpInput.value = player['Xp'];
            playerEditXpValue.appendChild(playerEditXpInput);
            playerEditInfoRow.appendChild(playerEditXpValue);

            const playerEditMoneyValue = document.createElement("td");
            const playerEditMoneyInput = document.createElement("input");
            playerEditMoneyInput.size = 5;
            playerEditMoneyInput.maxLength = 15;
            playerEditMoneyInput.name = "money_" + index;
            playerEditMoneyInput.value = player['Money'];
            playerEditMoneyValue.appendChild(playerEditMoneyInput);
            playerEditInfoRow.appendChild(playerEditMoneyValue);

            const playerEditNearValue = document.createElement("td");
            let playerEditNearTextNode = document.createTextNode("Unknown");
            if ('Industries' in this.json) {
                playerEditNearTextNode = document.createTextNode(this._nearestIndustry(player['Location'], this.json.Industries));
            }
            playerEditNearValue.appendChild(playerEditNearTextNode);
            playerEditInfoRow.appendChild(playerEditNearValue);

            const playerEditDeleteValue = document.createElement("td");
            const playerEditDeleteInput = document.createElement("input");
            playerEditDeleteInput.type = "checkbox"
            playerEditDeleteInput.name = "deletePlayer_" + index;
            playerEditDeleteValue.appendChild(playerEditDeleteInput);
            playerEditInfoRow.appendChild(playerEditDeleteValue);

            editPlayersTable.appendChild(playerEditInfoRow);
        }
    }

    /**
     *
     */
    getTracksAndBeds() {
        // const tracksAndBedsGroup = document.createElementNS(this.svgNS, "g");
        const tracksGroup = document.createElementNS(this.svgNS, "g");
        const bedsGroup = document.createElementNS(this.svgNS, "g");
        const maxSlopeLabelGroup = document.createElementNS(this.svgNS, "g");
        // tracksAndBedsGroup.setAttribute("class", "tracksandbeds display_show");
        tracksGroup.setAttribute("class", "tracks display_show");
        bedsGroup.setAttribute("class", "beds display_show");

        const slopeLabelGroup = Array(
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g")
        );

        slopeLabelGroup[0].setAttribute("class", "slopeLabel0 display_hide");
        slopeLabelGroup[1].setAttribute("class", "slopeLabel1 display_hide");
        slopeLabelGroup[2].setAttribute("class", "slopeLabel2 display_show");
        slopeLabelGroup[3].setAttribute("class", "slopeLabel3 display_show");
        maxSlopeLabelGroup.setAttribute("class", "maxSlopeLabel display_show");

        // [type, stroke-width, stroke]
        const drawOrder = {};
        drawOrder[1] = [1, 15, 'darkkhaki', 2]; // variable bank
        drawOrder[2] = [2, 15, 'darkkhaki', 2]; // constant bank
        drawOrder[5] = [5, 15, 'darkgrey', 3];  // variable wall
        drawOrder[6] = [6, 15, 'darkgrey', 3];  // constant wall
        drawOrder[7] = [7, 15, 'lightblue', 4]; // iron bridge
        drawOrder[3] = [3, 15, 'orange', 5];    // wooden bridge
        drawOrder[4] = [4, 3, 'black', 8];      // trendle track
        drawOrder[0] = [0, 3, 'black', 8];      // track        darkkhaki, darkgrey, orange, blue, black

        let slopecoords = [0, 0];

        if ('Splines' in this.json) {
            // for (const entry of drawOrder) {
            for (const spline of this.json.Splines) {
                let type = spline['Type'];
                let entry = drawOrder[type];
                const [current, strokeWidth, stroke, zindex] = entry;
                // if (type !== current) {
                //     continue
                // }

                let segments = spline['Segments'];
                if ([1, 2, 5, 6, 3, 7].indexOf(type) > -1) {
                    const bedSegment = document.createElementNS(this.svgNS, 'path');
                    let path = '';
                    let tool = '';
                    for (const segment of segments) {
                        //'<path d="M 100 100 L 300 100 L 200 300 z" fill="red" stroke="blue" stroke-width="3" />'
                        if (segment['Visible'] !== 1) {
                            tool = 'M';
                        } else {
                            tool = 'L';
                        }
                        let xStart = (this.imx - ((segment['LocationStart']['X'] - this.minX) / 100 * this.scale));
                        let yStart = (this.imy - ((segment['LocationStart']['Y'] - this.minY) / 100 * this.scale));
                        let xEnd = (this.imx - ((segment['LocationEnd']['X'] - this.minX) / 100 * this.scale));
                        let yEnd = (this.imy - ((segment['LocationEnd']['Y'] - this.minY) / 100 * this.scale));
                        let xCenter = (this.imx - ((segment['LocationCenter']['X'] - this.minX) / 100 * this.scale));
                        let yCenter = (this.imy - ((segment['LocationCenter']['Y'] - this.minY) / 100 * this.scale));

                        if (path === '') {
                            path = 'M ' + xStart + ',' + yStart + ' ';
                            path += tool + ' ' + xEnd + ',' + yEnd + ' ';
                        } else {
                            path += tool + ' ' + xEnd + ',' + yEnd + ' ';
                        }
                    }
                    bedSegment.setAttribute("d", path);
                    bedSegment.setAttribute("style", "z-index:"+zindex);
                    bedSegment.setAttribute("fill", 'none');
                    bedSegment.setAttribute("stroke", stroke);
                    bedSegment.setAttribute("stroke-width", strokeWidth.toString());

                    if (type === 3) {
                        bedSegment.setAttribute("class", "wooden");
                    } else if (type === 7) {
                        bedSegment.setAttribute("class", "iron");
                    }

                    bedsGroup.appendChild(bedSegment);
                } else {
                    // tracks..
                    for (const segment of segments) {
                        if (segment['Visible'] !== 1) {
                            continue
                        }

                        let xStart = (this.imx - ((segment['LocationStart']['X'] - this.minX) / 100 * this.scale));
                        let yStart = (this.imy - ((segment['LocationStart']['Y'] - this.minY) / 100 * this.scale));
                        let xEnd = (this.imx - ((segment['LocationEnd']['X'] - this.minX) / 100 * this.scale));
                        let yEnd = (this.imy - ((segment['LocationEnd']['Y'] - this.minY) / 100 * this.scale));
                        let xCenter = (this.imx - ((segment['LocationCenter']['X'] - this.minX) / 100 * this.scale));
                        let yCenter = (this.imy - ((segment['LocationCenter']['Y'] - this.minY) / 100 * this.scale));

                        const trackSegment = document.createElementNS(this.svgNS, 'line');
                        trackSegment.setAttribute("x1", xStart.toString());
                        trackSegment.setAttribute("y1", yStart.toString());
                        trackSegment.setAttribute("x2", xEnd.toString());
                        trackSegment.setAttribute("y2", yEnd.toString());
                        trackSegment.setAttribute("stroke", stroke);
                        trackSegment.setAttribute("stroke-width", strokeWidth.toString());
                        tracksGroup.appendChild(trackSegment);

                        let distance = Math.sqrt(
                            Math.pow(segment['LocationEnd']['X'] - segment['LocationStart']['X'], 2) +
                            Math.pow(segment['LocationEnd']['Y'] - segment['LocationStart']['Y'], 2) +
                            Math.pow(segment['LocationEnd']['Z'] - segment['LocationStart']['Z'], 2)
                        )

                        let slope = 0;
                        if (type in [4, 0]) {
                            const height = Math.abs(segment['LocationEnd']['Z'] - segment['LocationStart']['Z']);
                            const length = Math.sqrt(
                                Math.pow(segment['LocationEnd']['X'] - segment['LocationStart']['X'], 2) +
                                Math.pow(segment['LocationEnd']['Y'] - segment['LocationStart']['Y'], 2));

                            if (length === null || length === undefined || length === 0 || length === 0.0) {
                                const emptyLengthTrack = document.createElementNS(this.svgNS, "circle");
                                emptyLengthTrack.setAttribute("cx", xCenter.toString());
                                emptyLengthTrack.setAttribute("cy", yCenter.toString());
                                emptyLengthTrack.setAttribute("r", "10");
                                emptyLengthTrack.setAttribute("stroke", "red");
                                emptyLengthTrack.setAttribute("stroke-width", "2");
                                emptyLengthTrack.setAttribute("fill", "red");
                                tracksGroup.appendChild(emptyLengthTrack);

                                continue; //This may cause issues down the road. We may need to stop at this point and return the errors segment.
                            } else {
                                slope = (height * 100 / length);
                                if (slope > this.maxSlope) {
                                    slopecoords = [xCenter, yCenter];
                                }
                                this.maxSlope = Math.max(this.maxSlope, slope);
                            }
                        }

                        const slopeTriggerPrefix = '..';
                        const slopeTriggerDecimals = 1;
                        if (distance > 0 && (type === 4 || type === 0)) {
                            let degrees = null;
                            if (segment['LocationEnd']['X'] === segment['LocationStart']['X']) {
                                degrees = 90;
                            }
                            const tanA = (
                                (segment['LocationEnd']['Y'] - segment['LocationStart']['Y']) /
                                (segment['LocationEnd']['X'] - segment['LocationStart']['X'])
                            );
                            degrees = this._rad2deg(Math.atan(tanA));
                            if (degrees > 0) {
                                degrees -= 90;
                            } else {
                                degrees += 90;
                            }

                            if (this._getDistanceToNearestLabel([xCenter, yCenter]) > 60) {

                                let percentage = this._round(slope, slopeTriggerDecimals);

                                let numberX = Math.min(3, Math.floor(slope));

                                this.allLabels.push([xCenter, yCenter]);
                                const slopeLabel = document.createElementNS(this.svgNS, "text");
                                const textNode = document.createTextNode(slopeTriggerPrefix + percentage + "%");
                                slopeLabel.setAttribute("x", xCenter.toString());
                                slopeLabel.setAttribute("y", yCenter.toString());
                                slopeLabel.setAttribute("transform", "rotate(" + degrees + "," + xCenter + "," + yCenter + ")");
                                slopeLabel.appendChild(textNode);
                                slopeLabelGroup[numberX].appendChild(slopeLabel);
                            }
                        }
                    }
                }
            }
        }
        // }

        if (this.drawMaxSlope) {
            for (const r in [5, 4, 3, 2]) {
                const maxSlopeCircle = document.createElementNS(this.svgNS, "circle");
                maxSlopeCircle.setAttribute("cx", slopecoords[0].toString());
                maxSlopeCircle.setAttribute("cy", slopecoords[1].toString());
                maxSlopeCircle.setAttribute("r", (this.turnTableRadius * r).toString());
                maxSlopeCircle.setAttribute("stroke", "orange");
                maxSlopeCircle.setAttribute("stroke-width", "5");
                maxSlopeCircle.setAttribute("fill", "none");
                maxSlopeLabelGroup.appendChild(maxSlopeCircle);
                this.shapes.push(maxSlopeLabelGroup);
            }
        }
        this.shapes.push(bedsGroup);
        this.shapes.push(tracksGroup);
        this.shapes.push(slopeLabelGroup[0]);
        this.shapes.push(slopeLabelGroup[1]);
        this.shapes.push(slopeLabelGroup[2]);
        this.shapes.push(slopeLabelGroup[3]);
    }

    getSwitches() {
        if (!('Switchs' in this.json)) {
            return
        }

        const switchesGroup = document.createElementNS(this.svgNS, "g");
        switchesGroup.setAttribute("class", "switches display_show");

        for (const swtch of this.json.Switchs) { // can't use 'switch' as variable name
            let dir = false;
            const type = swtch['Type'];

            /**
             * 0 = SwitchLeft           = lever left switch going left
             * 1 = SwitchRight          = lever right switch going right
             * 2 =                      = Y
             * 3 =                      = Y mirror
             * 4 = SwitchRightMirror    = lever left switch going right
             * 5 = SwitchLeftMirror     = lever right switch going left
             * 6 = SwitchCross90        = cross
             */
            let state = swtch['Side'];
            switch (type) {
                case 0:
                    dir = -7;
                    state = !state;
                    break;
                case 1:
                case 3:
                case 4:
                    dir = 7;
                    break;
                case 2:
                    dir = -7;
                    break;
                case 5:
                    state = !state;
                    dir = -7;
                    break;
                case 6:
                    dir = 99;
                    break;
                default:
                    dir = 1;
            }

            if (!dir) {
                console.log("Switch error in switch " + swtch);
            }

            const rotation = this._deg2rad(swtch['Rotation'][1] - 90);
            const rotSide = this._deg2rad(swtch['Rotation'][1] - 90 + dir);
            const rotCross = this._deg2rad(swtch['Rotation'][1] + 180);

            const x = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale));

            if (dir === 99) { // Cross
                const crosslength = this.switchRadius / 10;
                const x2 = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotCross) * crosslength));
                const y2 = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotCross) * crosslength));
                const cx = x + (x2 - x) / 2;
                const cy = y + (y2 - y) / 2;

                let crossSegment = document.createElementNS(this.svgNS, "line");
                crossSegment.setAttribute("x1", x.toString());
                crossSegment.setAttribute("y1", y.toString());
                crossSegment.setAttribute("x2", x2.toString());
                crossSegment.setAttribute("y2", y2.toString());
                crossSegment.setAttribute("stroke", "black");
                crossSegment.setAttribute("stroke-width", "3");
                switchesGroup.appendChild(crossSegment);

                crossSegment = document.createElementNS(this.svgNS, "line");
                crossSegment.setAttribute("x1", (cx - (Math.cos(rotation) * crosslength)).toString());
                crossSegment.setAttribute("y1", (cy - (Math.sin(rotation) * crosslength)).toString());
                crossSegment.setAttribute("x2", (cx + (Math.cos(rotation) * crosslength)).toString());
                crossSegment.setAttribute("y2", (cy + (Math.sin(rotation) * crosslength)).toString());
                crossSegment.setAttribute("stroke", "black");
                crossSegment.setAttribute("stroke-width", "3");
                switchesGroup.appendChild(crossSegment);
            } else {
                const xStraight = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotation) * this.switchRadius / 2));
                const yStraight = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotation) * this.switchRadius / 2));
                const xSide = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotSide) * this.switchRadius / 2));
                const ySide = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotSide) * this.switchRadius / 2));

                if (state) {
                    let switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xStraight.toString());
                    switchSegment.setAttribute("y2", yStraight.toString());
                    switchSegment.setAttribute("stroke", "red");
                    switchSegment.setAttribute("stroke-width", "3");
                    switchesGroup.appendChild(switchSegment);

                    switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xSide.toString());
                    switchSegment.setAttribute("y2", ySide.toString());
                    switchSegment.setAttribute("stroke", "black");
                    switchSegment.setAttribute("stroke-width", "3");
                    switchesGroup.appendChild(switchSegment);
                } else {
                    let switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xSide.toString());
                    switchSegment.setAttribute("y2", ySide.toString());
                    switchSegment.setAttribute("stroke", "red");
                    switchSegment.setAttribute("stroke-width", "3");
                    switchesGroup.appendChild(switchSegment);

                    switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xStraight.toString());
                    switchSegment.setAttribute("y2", yStraight.toString());
                    switchSegment.setAttribute("stroke", "black");
                    switchSegment.setAttribute("stroke-width", "3");
                    switchesGroup.appendChild(switchSegment);
                }
            }
        }
        this.shapes.push(switchesGroup);
    }

    getTurntables() {
        if (!('Turntables' in this.json)) {
            return
        }

        const turntablesGroup = document.createElementNS(this.svgNS, "g");
        turntablesGroup.setAttribute("class", "turntables display_show");

        for (const turntable of this.json.Turntables) {
            /**
             * 0 = regular
             * 1 = light and nice
             */
            const rotation = this._deg2rad(turntable['Rotator'][1] + 90);
            const rotation2 = this._deg2rad(turntable['Rotator'][1] + 90 - turntable['Deck'][1]);
            this.turnTableRadius = 25;

            const x = (this.imx - ((turntable['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imx - ((turntable['Location'][1] - this.minX) / 100 * this.scale));
            const x2 = (this.imx - ((turntable['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotation) * this.turnTableRadius));
            const y2 = (this.imy - ((turntable['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotation) * this.turnTableRadius));
            const cx = x + (x2 - x) / 2;
            const cy = y + (y2 - y) / 2;

            const turntableCircle = document.createElementNS(this.svgNS, "circle");
            turntableCircle.setAttribute("cx", cx.toString());
            turntableCircle.setAttribute("cy", cy.toString());
            turntableCircle.setAttribute("r", (this.turnTableRadius / 2).toString());
            turntableCircle.setAttribute("stroke", "black");
            turntableCircle.setAttribute("stroke-width", "1");
            turntableCircle.setAttribute("fill", "lightyellow");
            turntablesGroup.appendChild(turntableCircle);

            const turntableLine = document.createElementNS(this.svgNS, "line");
            turntableLine.setAttribute("x1", (cx - (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y1", (cy - (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("x2", (cx + (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y2", (cy + (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("stroke", "black");
            turntableLine.setAttribute("stroke-width", "3");
            turntablesGroup.appendChild(turntableLine);
        }
        this.shapes.push(turntablesGroup);
    }

    getRollingStock() {
        if (!('Frames' in this.json)) {
            return
        }

        const rollingStockGroup = document.createElementNS(this.svgNS, "g");
        rollingStockGroup.setAttribute("class", "rollingstock display_show");

        const undergroundCartsTable = document.getElementById("undergroundCartsTable");
        const rollingStockTable = document.getElementById("rollingStockTable");
        const possibleCargos = {
            'flatcar_logs': ['log'],
            'flatcar_stakes': ['rail', 'lumber', 'beam', 'rawiron'],
            'flatcar_hopper': ['ironore', 'coal'],
            'flatcar_cordwood': ['cordwood'],
        }

        const cargoNames = {
            'log': 'Logs',
            'cordwood': 'Cordwood',
            'beam': 'Beams',
            'lumber': 'Lumber',
            'ironore': 'Iron Ore',
            'rail': 'Rails',
            'rawiron': 'Raw Iron',
            'coal': 'Coal',
            'steelpipe': 'Steel Pipes',
            'crate_tools': 'Tools',
            'crudeoil': 'Crude Oil',
            'oilbarrel': 'Oil Barrels',
        }

        const cartOptions = {
            'handcar': [this.engineRadius, 'white'],
            'porter_040': [this.engineRadius, 'black'],
            'porter_042': [this.engineRadius, 'black'],
            'eureka': [this.engineRadius, 'black'],
            'eureka_tender': [this.engineRadius, 'black'],
            'climax': [this.engineRadius, 'black'],
            'heisler': [this.engineRadius, 'black'],
            'class70': [this.engineRadius, 'black'],
            'class70_tender': [this.engineRadius, 'black'],
            'cooke260': [this.engineRadius, 'black'],
            'cooke260_tender': [this.engineRadius, 'black'],
            'flatcar_logs': [this.engineRadius / 3, 'red'],
            'flatcar_cordwood': [this.engineRadius / 3 * 2, 'orange'],
            'flatcar_stakes': [this.engineRadius / 3 * 2, 'yellow'],
            'flatcar_hopper': [this.engineRadius / 3 * 2, 'brown'],
            'boxcar': [this.engineRadius / 3 * 2, 'purple'],
            'flatcar_tanker': [this.engineRadius / 3 * 2, 'grey'],
        }

        let index = 0;
        for (const vehicle of this.json.Frames) {
            const x = (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale));
            if (['porter_040', 'porter_042', /*'handcar', */'eureka', 'climax', 'heisler', 'class70', 'cooke260'].indexOf(vehicle['Type']) >= 0) {
                const yl = (this.engineRadius / 3) * 2;
                const xl = (this.engineRadius / 2) * 2;
                const path = document.createElementNS(this.svgNS, "path");
                path.setAttribute("transform", "rotate(" + Math.round(vehicle['Rotation'][1]) + ", " + x + ", " + y + ")");
                path.setAttribute("d", "M" + (x - (this.engineRadius / 2)) + "," + y + " l " + (xl / 3) + "," + (yl / 2) + " l " + (xl / 3 * 2) + ",0 l 0,-" + yl + " l -" + (xl / 3 * 2) + ",0 z");
                path.setAttribute("fill", "purple");
                path.setAttribute("stroke", "black");
                path.setAttribute("stroke-width", "2");
                rollingStockGroup.appendChild(path);
            } else {
                const yl = (this.engineRadius / 3) * 2;
                let xl = this.engineRadius;

                if (vehicle['Type'].toLowerCase().indexOf('tender') !== -1) {
                    xl = xl / 3 * 2;
                }

                const path = document.createElementNS(this.svgNS, "path");
                path.setAttribute("d", "M" + Math.round(x) + "," + Math.round(y) + " m-" + (xl / 2) + ",-" + (yl / 2) + " h" + (xl - 4) + " a2,2 0 0 1 2,2 v" + (yl - 4) + " a2,2 0 0 1 -2,2 h-" + (xl - 4) + " a2,2 0 0 1 -2,-2 v-" + (yl - 4) + " a2,2 0 0 1 2,-2 z");
                path.setAttribute("fill", cartOptions[vehicle['Type']][1]);
                path.setAttribute("stroke", "black");
                path.setAttribute("stroke-width", "1");
                path.setAttribute("transform", "rotate(" + Math.round(vehicle['Rotation'][1]) + ", " + Math.round(x) + ", " + Math.round(y) + ")");
                rollingStockGroup.appendChild(path);
            }

            // const vehicleEllipse = document.createElementNS(this.svgNS, "ellipse");
            // vehicleEllipse.setAttribute("cx", x.toString());
            // vehicleEllipse.setAttribute("cy", y.toString());
            // vehicleEllipse.setAttribute("rx", (this.engineRadius / 2).toString());
            // vehicleEllipse.setAttribute("ry", (this.engineRadius / 3).toString());
            // vehicleEllipse.setAttribute("style", "fill:" + cartOptions[vehicle['Type']][1] + ";stroke:black;stroke-width:1");
            // vehicleEllipse.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale)) + ", " + (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale)) + ")");
            // this.shapes.push(vehicleEllipse);

            if (vehicle['Location'][2] < 1000) { // Assuming this checks for vehicles under ground, rename sunkenVehicle if otherwise.
                const sunkenVehicle = document.createElementNS(this.svgNS, "ellipse");
                sunkenVehicle.setAttribute("cx", x.toString());
                sunkenVehicle.setAttribute("cy", y.toString());
                sunkenVehicle.setAttribute("rx", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("ry", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("style", "fill:none;stroke:red;stroke-width:10");
                sunkenVehicle.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale)) + ", " + (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale)) + ")");
                rollingStockGroup.appendChild(sunkenVehicle);

                const sunkenVehicleLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode("&nbsp;&nbsp;" + vehicle['Location'][2]);
                sunkenVehicleLabel.setAttribute("x", x.toString());
                sunkenVehicleLabel.setAttribute("y", y.toString());
                sunkenVehicleLabel.appendChild(textNode);
                rollingStockGroup.appendChild(sunkenVehicleLabel);

                const cartInput = document.createElement("input");
                cartInput.name = "underground_" + index;
                cartInput.value = "1";
                cartInput.type = "hidden";
                undergroundCartsTable.appendChild(cartInput);
            }

            if (['porter_040', 'porter_042', /*'handcar', */'eureka', 'climax', 'heisler', 'class70', 'cooke260'].indexOf(vehicle['Type']) >= 0) {
                let name = vehicle['Name'].replace(/(<([^>]+)>)/gi, "").toUpperCase();
                if (!name) {
                    name = this._capitalize(vehicle['Type']);
                }

                let textRotation = vehicle['Rotation'][1];
                if (textRotation < 0) {
                    textRotation += 90;
                } else {
                    textRotation -= 90;
                }
                this.allLabels.push([x, y]);

                const vehicleLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode(".." + name);
                vehicleLabel.setAttribute("stroke", "black");
                vehicleLabel.setAttribute("fill", "white");
                vehicleLabel.setAttribute("font-size", "1em");
                vehicleLabel.setAttribute("x", x.toString());
                vehicleLabel.setAttribute("y", y.toString());
                vehicleLabel.setAttribute("transform", "rotate(" + textRotation + ", " + x + ", " + y + ")")
                vehicleLabel.appendChild(textNode);
                rollingStockGroup.appendChild(vehicleLabel);
            }

            let cargo = "firewood";
            let amount = vehicle['Tender']['Fuelamount'];
            let amountString = "tenderamount_";
            if (vehicle['Type'] in possibleCargos) {
                cargo = possibleCargos[vehicle['Type']];
                amount = vehicle['Freight']['Amount'];
                amountString = "freightamount_";
            }

            const rollingStockInfoRow = document.createElement("tr");

            const typeValue = document.createElement("td");
            const typeImage = document.createElement("img");
            typeImage.src = "/assets/images/" + vehicle['Type'] + ".png";
            typeValue.appendChild(typeImage);
            rollingStockInfoRow.appendChild(typeValue);

            const nameValue = document.createElement("td");
            const nameTextInput = document.createElement("input");
            nameTextInput.size = 5;
            nameTextInput.maxLength = 15;
            nameTextInput.name = "name_" + index;
            nameTextInput.value = vehicle['Name'].replace(/<\/?[^>]+(>|$)/g, "").toUpperCase()
            nameValue.appendChild(nameTextInput);
            rollingStockInfoRow.appendChild(nameValue);

            const numberValue = document.createElement("td");
            const numberTextInput = document.createElement("input");
            numberTextInput.size = 5;
            numberTextInput.maxLength = 15;
            numberTextInput.name = "number_" + index;
            numberTextInput.value = vehicle['Number'].replace(/<\/?[^>]+(>|$)/g, "");
            numberValue.appendChild(numberTextInput);
            rollingStockInfoRow.appendChild(numberValue);

            const nearValue = document.createElement("td");
            let nearTextNode = document.createTextNode("Unknown");
            if ('Industries' in this.json) {
                nearTextNode = document.createTextNode(this._nearestIndustry(vehicle['Location'], this.json.Industries));
            }
            nearValue.appendChild(nearTextNode);
            rollingStockInfoRow.appendChild(nearValue);

            const cargoValue = document.createElement("td");
            if (typeof cargo === "object") {
                const select = document.createElement("select");
                for (const cargoType of cargo) {
                    const option = document.createElement("option");
                    option.text = cargoNames[cargoType];
                    option.value = cargoType;
                    option.selected = vehicle['Freight']['Type'] === cargoType;
                    select.add(option);
                }
                cargoValue.appendChild(select);
            } else {
                const cargoTextNode = document.createTextNode(cargo);
                cargoValue.appendChild(cargoTextNode);
            }
            rollingStockInfoRow.appendChild(cargoValue);

            const amountValue = document.createElement("td");
            const amountTextInput = document.createElement("input");
            amountTextInput.name = amountString + index;
            amountTextInput.value = amount;
            amountTextInput.size = 2;
            amountTextInput.maxLength = 4;
            amountValue.appendChild(amountTextInput);
            rollingStockInfoRow.appendChild(amountValue);

            rollingStockTable.appendChild(rollingStockInfoRow);

            index++;
        }
        this.shapes.push(rollingStockGroup);
    }

    getIndustries() {
        if (!('Industries' in this.json)) {
            return
        }

        const industriesTable = document.getElementById("industriesTable");
        let index = 0;
        for (const industry of this.json.Industries) {
            let name = '';
            let rotation = 0;
            let xoff = 0;
            let yoff = 0;
            let pis = [];
            let pos = [];

            switch (industry['Type']) {
                case 1:
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    pos = ['logs_p.svg', 'cordwood_p.svg', 'cordwood_p.svg', 'logs_p.svg'];
                    name = "Logging Camp";
                    rotation = 0;
                    xoff = -70;
                    yoff = -30;
                    break;
                case 2:
                    name = 'Sawmill';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['lumber_p.svg', 'beams_p.svg'];
                    pis = ['logs_p.svg'];
                    xoff = -35;
                    yoff = -15;
                    rotation = 45;
                    break;
                case 3:
                    name = 'Smelter';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['iron_p.svg', 'rails_p.svg'];
                    pis = ['ironore_p.svg', 'cordwood_p.svg'];
                    rotation = 90;
                    break;
                case 4:
                    name = 'Ironworks';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['pipes_p.svg', 'tools_p.svg'];
                    pis = ['iron_p.svg', 'coal_p.svg'];
                    rotation = 90;
                    break;
                case 5:
                    name = 'Oilfield';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['pipes_p.svg', 'beams_p.svg', 'tools_p.svg'];
                    pos = ['oil_p.svg'];
                    rotation = 0;
                    break;
                case 6:
                    name = 'Refinery';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['oil_p.svg', 'pipes_p.svg', 'lumber_p.svg'];
                    pos = ['barrels_p.svg', 'barrels_p.svg'];
                    rotation = 0;
                    break;
                case 7:
                    name = 'Coal Mine';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['beams_p.svg', 'rails_p.svg'];
                    pos = ['coal_p.svg'];
                    rotation = -20;
                    xoff = -20;
                    yoff = 20;
                    break;
                case 8:
                    name = 'Iron Mine';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['lumber_p.svg', 'beams_p.svg'];
                    pos = ['ironore_p.svg'];
                    rotation = 45;
                    yoff = +50;
                    xoff = -20;
                    break;
                case 9:
                    name = 'Freight Depot';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    rotation = 90;
                    break;
                case 10:
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    pis = ['cordwood_p.svg'];
                    name = 'F#' + index;
                    rotation = (industry['Rotation'][1] > 0) ? (industry['Rotation'][1] - 90) : (industry['Rotation'][1] + 90);
                    const x = (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale));
                    const y = (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale));
                    const path = document.createElementNS(this.svgNS, "path");
                    path.setAttribute("transform", "rotate(" + Math.round(industry['Rotation'][1]) + ", " + x + ", " + y + ")");
                    path.setAttribute("d", "M" + x + "," + y + " m-18,-15 l10,0 l0,30 l-10,0 z");
                    path.setAttribute("fill", "orange");
                    path.setAttribute("stroke", "brown");
                    this.shapes.push(path);
                    xoff = -20;
                    yoff = 0;
                    break;
                default:
                    console.log("Unknown industry: " + JSON.stringify(industry, null, 2));
            }

            const industryLabel = document.createElementNS(this.svgNS, "text");
            const textNode = document.createTextNode(name);
            industryLabel.setAttribute("x", (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale)).toString());
            industryLabel.setAttribute("y", (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale)).toString());
            industryLabel.setAttribute("transform", "rotate(" + rotation + ", " + (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale) + xoff) + ", " + (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale) + yoff) + ")");
            industryLabel.appendChild(textNode);
            this.shapes.push(industryLabel);

            const eductRow = document.createElement("tr");
            eductRow.setAttribute("class", "export__educts");
            const eductnameColumn = document.createElement("td");
            const eductnameColumnText = document.createTextNode(name + " Educts");
            eductnameColumn.appendChild(eductnameColumnText);
            eductRow.appendChild(eductnameColumn);
            for (let i = 0; i < industry['EductsStored'].length; i++) {
                const itemColumn = document.createElement("td");
                const itemInput = document.createElement("input");
                itemInput.size = 5;
                itemInput.maxLength = 15;
                itemInput.name = "educt" + i + "_" + index;
                itemInput.value = industry['EductsStored'][i];

                if (typeof pis[i] !== "undefined") {
                    const itemImage = document.createElement("img");
                    itemImage.setAttribute("style", "float:right");
                    itemImage.setAttribute("src", "/assets/images/" + pis[i]);
                    itemImage.setAttribute("height", "30");
                    itemColumn.appendChild(itemImage);
                }

                itemColumn.appendChild(itemInput);
                eductRow.appendChild(itemColumn);
            }
            industriesTable.appendChild(eductRow);

            const productRow = document.createElement("tr");
            productRow.setAttribute("class", "export__products");
            const productNameColumn = document.createElement("td");
            const productNameColumnText = document.createTextNode(name + " Products");
            productNameColumn.appendChild(productNameColumnText);
            productRow.appendChild(productNameColumn);
            for (let i = 0; i < industry['ProductsStored'].length; i++) {
                const itemColumn = document.createElement("td");
                const itemInput = document.createElement("input");
                itemInput.size = 5;
                itemInput.maxLength = 15;
                itemInput.name = "product" + i + "_" + index;
                itemInput.value = industry['ProductsStored'][i];

                if (typeof pos[i] !== "undefined") {
                    const itemImage = document.createElement("img");
                    itemImage.setAttribute("style", "float:right");
                    itemImage.setAttribute("src", "/assets/images/" + pos[i]);
                    itemImage.setAttribute("height", "30");
                    itemColumn.appendChild(itemImage);
                }

                itemColumn.appendChild(itemInput);
                productRow.appendChild(itemColumn);
            }
            industriesTable.appendChild(productRow);

            index += 1;
        }
    }

    getWaterTowers() {
        if (!('Watertowers' in this.json)) {
            return
        }

        for (const tower of this.json.Watertowers) {
            const x = this.imx - ((tower['Location'][0] - this.minX) / 100 * this.scale);
            const y = this.imy - ((tower['Location'][1] - this.minY) / 100 * this.scale);

            const waterTower = document.createElementNS(this.svgNS, "path");
            waterTower.setAttribute("transform", "rotate(" + Math.round(tower['Rotation'][1]) + ", " + x + ", " + y + ")");
            waterTower.setAttribute("d", "M" + x + "," + y + " m -5,-5 l10,0 l0,3 l3,0 l0,4 l-3,0 l0,3 l-10,0 z");
            waterTower.setAttribute("fill", "lightblue");
            waterTower.setAttribute("stroke", "black");
            waterTower.setAttribute("stroke-width", "1");
            this.shapes.push(waterTower);

            const waterTowerCircle = document.createElementNS(this.svgNS, "circle");
            waterTowerCircle.setAttribute("cx", x.toString());
            waterTowerCircle.setAttribute("cy", y.toString());
            waterTowerCircle.setAttribute("r", "3");
            waterTowerCircle.setAttribute("fill", "blue");
            this.shapes.push(waterTowerCircle);
        }
    }

    getReplantableTrees() {
        if (!('Removed' in this.json)) {
            return
        }

        const firstTreeGroup = document.createElementNS(this.svgNS, "g");
        firstTreeGroup.setAttribute("class", "trees_default display_hide");
        const userTreeGroup = document.createElementNS(this.svgNS, "g");
        userTreeGroup.setAttribute("class", "trees_user display_hide");

        for (let i = 0; i < this.json['Removed']['Vegetation'].length; i++) {
            const tree = this.json['Removed']['Vegetation'][i];
            const treeX = Math.floor((200000 + tree[0]) / 100000);
            const treeY = Math.floor((200000 + tree[1]) / 100000);

            let minDistanceToSomething = 80000000;

            try {
                for (const segment of this.json['Segments'][treeX][treeY]) {
                    if (segment['LocationCenter']['X'] < tree[0] - 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['X'] > tree[0] + 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['Y'] < tree[1] - 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['Y'] > tree[1] + 6000) {
                        continue;
                    }
                    minDistanceToSomething = Math.min(minDistanceToSomething, this._dist(tree, segment['LocationCenter']));
                }
            } catch (err) {
            }
            if (minDistanceToSomething > 700) {
                const x = (this.imx - ((tree[0] - this.minX) / 100 * this.scale));
                const y = (this.imy - ((tree[1] - this.minY) / 100 * this.scale));
                const treeCircle = document.createElementNS(this.svgNS, "circle");
                treeCircle.setAttribute("cx", x.toString());
                treeCircle.setAttribute("cy", y.toString());
                treeCircle.setAttribute("r", "6");
                treeCircle.setAttribute("stroke", "darkgreen");
                treeCircle.setAttribute("stroke-width", "2");
                if (i < this.initialTreesDown) {
                    treeCircle.setAttribute("fill", "orange");
                    firstTreeGroup.appendChild(treeCircle);
                } else {
                    treeCircle.setAttribute("fill", "green");
                    userTreeGroup.appendChild(treeCircle);
                }
            }
        }

        this.shapes.push(firstTreeGroup);
        this.shapes.push(userTreeGroup);
    }

    _getDistanceToNearestLabel(newLabel) {
        let minDistance = 8000;
        for (const oldLabel of this.allLabels) {
            minDistance = Math.min(
                minDistance,
                Math.sqrt(
                    Math.pow(newLabel[0] - oldLabel[0], 2) +
                    Math.pow(newLabel[1] - oldLabel[1], 2)
                ));
        }

        return minDistance;
    }

    _round(value, decimals) {
        // noinspection JSCheckFunctionSignatures
        return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
    }

    _rad2deg(radians) {
        return (radians * (180 / Math.PI));
    }

    _deg2rad(degrees) {
        return degrees * (Math.PI / 180);
    }

    _capitalize(string) {
        return String(string).charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    }

    _nearestIndustry(coords, industryCoords) {
        let minDist = 800000;
        let ind = 0;
        for (const i of industryCoords) {
            if (i['Type'] < 10) {
                const d = this._dist(i['Location'], coords);
                if (d < minDist) {
                    minDist = d;
                    ind = i['Type'];
                }
            }
        }

        switch (ind) {
            case 1:
                name = 'Logging Camp';
                break;
            case 2:
                name = 'Sawmill';
                break;
            case 3:
                name = 'Smelter';
                break;
            case 4:
                name = 'Ironworks';
                break;
            case 5:
                name = 'Oilfield';
                break;
            case 6:
                name = 'Refinery';
                break;
            case 7:
                name = 'Coal Mine';
                break;
            case 8:
                name = 'Iron Mine';
                break;
            case 9:
                name = 'Freight Depot';
                break;
        }

        return name;
    }

    _dist(a, b) {
        return Math.sqrt(
            Math.pow(a[0] - b['X'], 2) +
            Math.pow(a[1] - b['Y'], 2)
        ).valueOf();
    }
}
