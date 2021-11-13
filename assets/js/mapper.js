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
        this.totalTrackLength = 0;
        this.totalSwitches = 0;
        this.totalLocos = 0;
        this.totalCarts = 0;
        this.maxSlope = 0;
        this.allLabels = [[0, 0]];
    }

    drawSVG(htmlElement) {
        this.svgTag = document.getElementById(htmlElement);
        this.getTracksAndBeds();
        this.getSwitches();
        this.getTurntables();
        this.getRollingStock();
        this.getIndustries();
        this.getWaterTowers();

        for (const shape of this.shapes) {
            this.svgTag.appendChild(shape);
        }
    }

    populatePlayerTable() {
        if (!('Players' in this.json)) {
            return null
        }
        const playerInfoTable = document.getElementById("playerTable");
        for (const player of this.json.Players) {
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

            playerInfoTable.appendChild(playerInfoRow);
        }
    }

    getTracksAndBeds() {
        const drawOrder = [
            // [type, stroke-width, stroke]
            [1, 15, 'darkkhaki'], // variable bank
            [2, 15, 'darkkhaki'], // constant bank
            [5, 15, 'darkgrey'],  // variable wall
            [6, 15, 'darkgrey'],  // constant wall
            [7, 15, 'lightblue'], // iron bridge
            [3, 15, 'orange'],    // wooden bridge
            [4, 3, 'black'],      // trendle track
            [0, 3, 'black']       // track        darkkhaki, darkgrey, orange, blue, black
        ]

        let slopecoords = [0, 0];

        for (const entry of drawOrder) {
            const [current, strokeWidth, stroke] = entry;
            if ('Splines' in this.json) {
                for (const spline of this.json.Splines) {
                    let type = spline['Type'];
                    if (type !== current) {
                        continue
                    }

                    let segments = spline['Segments'];
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
                        this.shapes.push(trackSegment);

                        let distance = Math.sqrt(
                            Math.pow(segment['LocationEnd']['X'] - segment['LocationStart']['X'], 2) +
                            Math.pow(segment['LocationEnd']['Y'] - segment['LocationStart']['Y'], 2) +
                            Math.pow(segment['LocationEnd']['Z'] - segment['LocationStart']['Z'], 2)
                        )

                        let slope = 0;
                        if (type in [4, 0]) {
                            this.totalTrackLength += distance;

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
                                this.shapes.push(emptyLengthTrack);

                                continue; //This may cause issues down the road. We may need to stop at this point and return the errors segment.
                            } else {
                                let slope = (height * 100 / length);
                                if (slope > this.maxSlope) {
                                    slopecoords = [xCenter, yCenter];
                                }
                                this.maxSlope = Math.max(this.maxSlope, slope);
                            }
                        }

                        const slopeTrigger = 2;
                        const slopeTriggerPrefix = '..';
                        const slopeTriggerDecimals = 1;
                        if (distance > 0 && type in [4, 0]) {
                            if (Math.abs(slope) > slopeTrigger) {
                                const tanA = (
                                    (segment['LocationEnd']['Y'] - segment['LocationStart']['Y']) /
                                    (segment['LocationEnd']['X'] - segment['LocationStart']['X'])
                                );
                                let degrees = this._rad2deg(Math.atan(tanA));
                                if (degrees > 0) {
                                    degrees -= 90;
                                } else {
                                    degrees += 90;
                                }

                                if (this._getDistanceToNearestLabel([xCenter, yCenter]) > 60) {
                                    this.allLabels.push([xCenter, yCenter]);
                                    const slopeLabel = document.createElementNS(this.svgNS, "text");
                                    const textNode = document.createTextNode(slopeTriggerPrefix + this._round(slope, slopeTriggerDecimals) + "%");
                                    slopeLabel.setAttribute("x", xCenter.toString());
                                    slopeLabel.setAttribute("y", yCenter.toString());
                                    slopeLabel.setAttribute("transform", "rotate(" + degrees + "," + xCenter + "," + yCenter + ")");
                                    slopeLabel.appendChild(textNode);
                                    this.shapes.push(slopeLabel);
                                }
                            }
                        }
                    }
                }
            }
        }

        if (this.drawMaxSlope) {
            for (const r in [5, 4, 3, 2]) {
                const maxSlopeCircle = document.createElementNS(this.svgNS, "circle");
                maxSlopeCircle.setAttribute("cx", slopecoords[0].toString());
                maxSlopeCircle.setAttribute("cy", slopecoords[1].toString());
                maxSlopeCircle.setAttribute("r", (this.turnTableRadius * r).toString());
                maxSlopeCircle.setAttribute("stroke", "orange");
                maxSlopeCircle.setAttribute("stroke-width", "5");
                maxSlopeCircle.setAttribute("fill", "none");
                this.shapes.push(maxSlopeCircle);
            }
        }
    }

    getSwitches() {
        if (!('Switchs' in this.json)) {
            return
        }

        for (const swtch of this.json.Switchs) { // can't use 'switch' as variable name
            this.totalSwitches += 1;
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
                this.shapes.push(crossSegment);

                crossSegment = document.createElementNS(this.svgNS, "line");
                crossSegment.setAttribute("x1", (cx - (Math.cos(rotation) * crosslength)).toString());
                crossSegment.setAttribute("y1", (cy - (Math.sin(rotation) * crosslength)).toString());
                crossSegment.setAttribute("x2", (cx + (Math.cos(rotation) * crosslength)).toString());
                crossSegment.setAttribute("y2", (cy + (Math.sin(rotation) * crosslength)).toString());
                crossSegment.setAttribute("stroke", "black");
                crossSegment.setAttribute("stroke-width", "3");
                this.shapes.push(crossSegment);
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
                    this.shapes.push(switchSegment);

                    switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xSide.toString());
                    switchSegment.setAttribute("y2", ySide.toString());
                    switchSegment.setAttribute("stroke", "black");
                    switchSegment.setAttribute("stroke-width", "3");
                    this.shapes.push(switchSegment);
                } else {
                    let switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xSide.toString());
                    switchSegment.setAttribute("y2", ySide.toString());
                    switchSegment.setAttribute("stroke", "red");
                    switchSegment.setAttribute("stroke-width", "3");
                    this.shapes.push(switchSegment);

                    switchSegment = document.createElementNS(this.svgNS, "line");
                    switchSegment.setAttribute("x1", x.toString());
                    switchSegment.setAttribute("y1", y.toString());
                    switchSegment.setAttribute("x2", xStraight.toString());
                    switchSegment.setAttribute("y2", yStraight.toString());
                    switchSegment.setAttribute("stroke", "black");
                    switchSegment.setAttribute("stroke-width", "3");
                    this.shapes.push(switchSegment);
                }
            }
        }
    }

    getTurntables() {
        if (!('Turntables' in this.json)) {
            return
        }

        for (const turntable of this.json.Turntables) {
            /**
             * 0 = regular
             * 1 = light and nice
             */
            const type = turntable['Type'];

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
            this.shapes.push(turntableCircle);

            const turntableLine = document.createElementNS(this.svgNS, "line");
            turntableLine.setAttribute("x1", (cx - (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y1", (cy - (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("x2", (cx + (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y2", (cy + (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("stroke", "black");
            turntableLine.setAttribute("stroke-width", "3");
            this.shapes.push(turntableLine);
        }
    }

    getRollingStock() {
        if (!('Frames' in this.json)) {
            return
        }

        const cartOptions = {
            'handcar': [this.engineRadius, 'black'],
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

        for (const vehicle of this.json.Frames) {
            const x = (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale));
            const vehicleEllipse = document.createElementNS(this.svgNS, "ellipse");
            vehicleEllipse.setAttribute("cx", x.toString());
            vehicleEllipse.setAttribute("cy", y.toString());
            vehicleEllipse.setAttribute("rx", (this.engineRadius / 2).toString());
            vehicleEllipse.setAttribute("ry", (this.engineRadius / 3).toString());
            vehicleEllipse.setAttribute("style", "fill:" + cartOptions[vehicle['Type']][1] + ";stroke:black;stroke-width:1");
            vehicleEllipse.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale)) + ", " + (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale)) + ")");
            this.shapes.push(vehicleEllipse);

            if (vehicle['Location'][2] < 1000) { // Assuming this checks for vehicles under ground, rename sunkenVehicle if otherwise.
                const sunkenVehicle = document.createElementNS(this.svgNS, "ellipse");
                sunkenVehicle.setAttribute("cx", x.toString());
                sunkenVehicle.setAttribute("cy", y.toString());
                sunkenVehicle.setAttribute("rx", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("ry", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("style", "fill:none;stroke:red;stroke-width:10");
                sunkenVehicle.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale)) + ", " + (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale)) + ")");
                this.shapes.push(sunkenVehicle);

                const sunkenVehicleLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode("&nbsp;&nbsp;" + vehicle['Location'][2]);
                sunkenVehicleLabel.setAttribute("x", x.toString());
                sunkenVehicleLabel.setAttribute("y", y.toString());
                sunkenVehicleLabel.appendChild(textNode);
                this.shapes.push(sunkenVehicleLabel);
            }

            if (['porter_040', 'porter_042', 'handcar', 'eureka', 'climax', 'heisler', 'class70', 'cooke260'].indexOf(vehicle['Type']) >= 0) {
                this.totalLocos += 1;
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
                vehicleLabel.setAttribute("x", x.toString());
                vehicleLabel.setAttribute("y", y.toString());
                vehicleLabel.setAttribute("transform", "rotate(" + textRotation + ", " + x + ", " + y + ")")
                vehicleLabel.appendChild(textNode);
                this.shapes.push(vehicleLabel);
            } else {
                this.totalCarts += 1;
            }
        }
    }

    getIndustries() {
        if (!('Industries' in this.json)) {
            return
        }

        let index = 0;
        for (const industry of this.json.Industries) {
            let name = '';
            let rotation = 0;
            let xoff = 0;
            let yoff = 0;

            switch (industry['Type']) {
                case 1:
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
                    rotation = 90;
                    break;
                case 4:
                    name = 'Ironworks';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    rotation = 90;
                    break;
                case 5:
                    name = 'Oilfield';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    rotation = 0;
                    break;
                case 6:
                    name = 'Refinery';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    rotation = 0;
                    break;
                case 7:
                    name = 'Coal Mine';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
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
                    name = 'F#' + index;
                    rotation = (industry['Rotation'][1] > 0) ? (industry['Rotation'][1] - 90) : (industry['Rotation'][1] + 90);
                    break;
                default:
                    console.log("Unknown industry: " + JSON.stringify(industry, null, 2));
            }

            try {
                const industryLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode(name);
                industryLabel.setAttribute("x", (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale) + xoff).toString());
                industryLabel.setAttribute("y", (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale) + yoff).toString());
                industryLabel.setAttribute("transform", "rotate(" + rotation + ", " + (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale) + xoff) + ", " + (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale) + yoff) + ")");
                industryLabel.appendChild(textNode);
                this.shapes.push(industryLabel);
            } catch (err) {
                console.log(err);
            }

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

            const waterTower = document.createElementNS(this.svgNS, "text");
            const textNode = document.createTextNode("W");
            waterTower.setAttribute("x", x.toString());
            waterTower.setAttribute("y", y.toString());
            waterTower.appendChild(textNode);
            this.shapes.push(waterTower);
        }
    }

    _getDistanceToNearestLabel(newLabel) {
        const minDistance = 8000;
        for (const oldLabel of this.allLabels) {
            const minDistance = Math.min(
                minDistance,
                Math.sqrt(
                    Math.pow(newLabel[0] - oldLabel[0], 2) +
                    Math.pow(newLabel[1] - oldLabel[1], 2)
                ));
        }

        return minDistance;
    }

    _round(value, decimals) {
        return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
    }

    _rad2deg(radians) {
        return (radians * (180 / Math.PI));
    }

    _deg2rad(degrees) {
        return degrees * (Math.PI / 180);
    }

    _capitalize(string) {
        return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    }

}
