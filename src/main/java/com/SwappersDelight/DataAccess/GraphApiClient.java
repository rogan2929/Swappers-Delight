/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.DataAccess;

import org.springframework.social.facebook.api.Facebook;

/**
 *
 * @author rogan2929
 */
public class GraphApiClient {
    private final String appId;
    private final String appSecret;
    private String appSecretProof;
    
    private Facebook facebook;

    public GraphApiClient() {
        this.appId = "652991661414427";
        this.appSecret = "b8447ce73d2dcfccde6e30931cfb0a90";
        
    }
    
    
}
