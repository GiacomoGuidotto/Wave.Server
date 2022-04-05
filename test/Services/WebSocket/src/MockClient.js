import styles from './App.module.css';
import {useEffect, useRef, useState} from "react";


function MockClient() {

    // ==== WebSocket connection functions =========================================================
    const [connectionStatus, setConnectionStatus] = useState(false);
    const [connectionError, setConnectionError] = useState('');

    const channel = useRef(null);

    function connect() {
        channel.current = new WebSocket('ws://localhost:8000');

        channel.current.onopen = () => {
            setConnectionStatus(true);
        }

        channel.current.onclose = () => {
            setConnectionStatus(false);
            setReceivedPackets([]);
            setPacketError('');
            setPacket({value: '', caret: -1, target: null});
        }

        channel.current.onmessage = (packet) => {
            packet = JSON.parse(packet.data);
            console.log("New message:", JSON.stringify(packet, undefined, 2))
            setReceivedPackets([...receivedPackets, packet])
        }

        channel.current.onerror = () => {
            setConnectionError('CONNECTION FAILED')
        }
    }

    function toggleConnection() {
        if (connectionStatus) {
            channel.current.close();
        } else {
            connect();
        }
    }

    // ==== Send packet functions ==================================================================
    const [packet, setPacket] = useState({value: '', caret: -1, target: null});
    const [packetError, setPacketError] = useState('');

    function handleChange(event) {
        setPacketError('');
        setPacket({value: event.target.value, caret: -1, target: event.target});
    }

    useEffect(() => {
        if (packet.caret >= 0) {
            packet.target.setSelectionRange(
                packet.caret + 2,
                packet.caret + 2
            );
        }
    }, [packet]);

    function handleTab(event) {
        let content = event.target.value;
        let caret = event.target.selectionStart;

        if (event.key === 'Tab') {

            event.preventDefault();

            let newText = content.substring(0, caret) + ' '.repeat(2) + content.substring(caret);

            setPacket({value: newText, caret: caret, target: event.target});

        }

        if (event.ctrlKey && event.key === 'Enter') {
            submit(event);
        }
    }

    function submit(e) {
        e.preventDefault();
        try {
            const text = JSON.parse(packet.value);
            channel.current.send(JSON.stringify(text));
            setPacket({value: '', caret: -1, target: null});
        } catch (e) {
            setPacketError('Incorrect JSON')
        }
    }

    // ==== Received packets functions =============================================================
    const [receivedPackets, setReceivedPackets] = useState([]);

    // ==== DOM functions ==========================================================================
    useEffect(() => {
        connect();
    }, [])

    return (
        <>
            <header className={styles.header}>
                <div className={styles.titleBox}>
                    <div className={styles.title}>Wave Channel</div>
                    <div>WebSocket mock client</div>
                </div>
            </header>
            <main>
                <div className={styles.connectionBox}>
                    <div className={styles.connectionStatusBox}>
                        Channel connection status:
                        {connectionError.length === 0 ?
                            <div className={connectionStatus ? styles.connectionUp : styles.connectionDown}>
                                {connectionStatus ? 'OPEN' : 'CLOSED'}
                            </div> :
                            <div className={styles.connectionDown}>
                                {connectionError}
                            </div>}
                    </div>
                    <button className={styles.connectionButton} onClick={toggleConnection}>
                        {connectionStatus ? 'SHUTDOWN' : 'OPEN'}
                    </button>
                </div>
                <div className={styles.body}>
                    <div className={styles.conversationBox}>
                        {!connectionStatus ?
                            <div className={styles.conversationError}>
                                <div> CONVERSATION UNAVAILABLE <br/>
                                    open the connection first
                                </div>
                            </div> : null}
                        <div className={styles.sendPacketBox}>
                            <div className={styles.sendPacketHeader}>
                                Send a packet
                            </div>
                            <div className={styles.sendPacket}>
                                <textarea
                                    onSubmit={submit}
                                    value={packet.value}
                                    onChange={handleChange}
                                    onKeyDown={handleTab}/>
                                <div className={styles.sendPacketCommands}>
                                    <button className={styles.sendPacketSubmit} onClick={submit}>Send</button>
                                    {packetError.length !== 0 ?
                                        <div className={styles.sendPacketError}>
                                            {packetError}
                                        </div> : null
                                    }
                                </div>
                            </div>
                        </div>
                        <div className={styles.receivedPacketsBox}>
                            <div className={styles.receivedPacketHeader}>
                                Received packets
                            </div>
                            {receivedPackets.length !== 0 ? <div className={styles.receivedPacket}>
                                {receivedPackets.map((packet, index) =>
                                    <div key={index} className={styles.packet}>
                                        <div className={styles.packetNumber}>
                                            <div>{index + 1}</div>
                                        </div>
                                        <div className={styles.packetContent}>
                                            {JSON.stringify(packet, undefined, 2)}
                                        </div>
                                    </div>
                                )}
                            </div> : null}
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}

export default MockClient;