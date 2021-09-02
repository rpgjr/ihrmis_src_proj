import React, { useState } from 'react';
import { MdClose } from 'react-icons/md'
import ButtonComponent from '../button_component/button_component.js.js';
import './modal_component.css'

const ModalComponent = (props) => {

    const modalViewFunction = () => {
        return (
            <div className="modal-component-div">
                <form>
                    <div className="mcf-header">
                        <h3>{props.title}</h3>
                        <button type="button" 
                                onClick={props.onClose}
                                style={{
                                    padding:"0px",
                                    margin:"0px",
                                    background:"whitesmoke",
                                    border:"none",
                                }}>
                            <MdClose  size="20px"/>
                        </button>
                    </div>
                    <hr style={{border:"1px solid rgba(70, 70, 70, 0.1)"}}/>
                    <div className="mcf-body">
                        {props.children}
                    </div>
                    <hr style={{border:"1px solid rgba(70, 70, 70, 0.1)"}}/>
                    <div className="mcf-footer">
                        <div >
                            <ButtonComponent
                                className="ft-button"
                                type="button"
                                bgColor="rgb(230, 230, 230)"
                                border="1px solid rgba(70, 70, 70, 0.8)"
                                onClick={props.onClose}
                                buttonName={props.onCloseName}/>

                        </div>
                        <div className="margin-left-1">
                            <ButtonComponent 
                                type="button"
                                buttonName={props.onSubmitName}/>
                        </div>
                    </div>
                </form>
            </div>
        );
    }

    return (
        <React.Fragment>
            {props.isDisplay ? modalViewFunction() : null}
        </React.Fragment>
    );
}

ModalComponent.defaultProps = {
    title: "Title",
    isDisplay: false,
    onSubmitName: "Submit",
    onCloseName: "Close"
}

export default ModalComponent; 