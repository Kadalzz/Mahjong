import qrcode from 'qrcode-generator';

document.querySelectorAll('[data-qr]').forEach((element) => {
    const qr = qrcode(0, 'M');
    qr.addData(element.dataset.qr);
    qr.make();
    element.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 12, scalable: true });
});
