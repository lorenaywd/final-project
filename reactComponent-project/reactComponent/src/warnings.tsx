import React, { useEffect, useState } from 'react';
import './warnings.css';

interface Warning {
    message: string;
    loggerName: string;
    level: string;
    user: string;
}


function FetchData() {
    const [warnings, setWarnings] = useState<Warning[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetch("http://localhost:3000/products")
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then((data: Warning[]) => {
                // Filtrer les données pour inclure uniquement celles avec loggerName: "alertApp"
                const filteredData = data.filter(item => item.loggerName === "alertApp");
                setWarnings(filteredData);
                setError(null);
            })
            .catch(err => {
                console.error("Error fetching data:", err);
                setError('Error fetching data. Please try again later.');
            });
    }, []);

    if (error) {
        return <div className="container">{error}</div>;
    }

    return (
        <div className="container">
            <h2 className="title">WARNINGS</h2>
            <ul className="warning-list">
                {warnings.map((list, index) => (
                    <li key={index} className="warning-item">
                        <p className="logger-name">{list.loggerName}</p>
                        <p className="level">Level: {list.level}</p>
                        <p className="message">Message:{list.message}</p>
                        <p className="user">Utilisateur: {list.user}</p>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default FetchData;